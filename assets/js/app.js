/**
 * Alarme a Distância - Aplicação Principal (JavaScript Mobile First)
 * Integra GPS, Leaflet, Cálculo Haversine, WakeLock e Controle do Trajeto
 */

const App = {
    // Estado da Aplicação
    map: null,
    userMarker: null,
    destMarker: null,
    radiusCircle: null,
    
    watchId: null,
    wakeLock: null,
    
    currentLocation: null, // { lat, lng, accuracy }
    destination: null,     // { lat, lng, name }
    
    radiusMeters: 300,
    alarmType: 'ambos',
    alarmSound: 'sirene',
    
    status: 'inativo',     // 'inativo' | 'ativo' | 'pausado' | 'acionado'
    tripId: null,
    alarmTriggered: false,

    /**
     * Inicialização
     */
    init() {
        this.initMap();
        this.bindEvents();
        this.requestNotificationPermission();
        this.initDefaultValues();
        this.startGpsTracking(false); // Inicia leitura passiva de localização
    },

    /**
     * Inicializa o mapa com Leaflet
     */
    initMap() {
        // Padrão Brasil / São Paulo até obter GPS real
        const defaultLat = -23.55052;
        const defaultLng = -46.633308;

        this.map = L.map('map', {
            zoomControl: true,
            attributionControl: false
        }).setView([defaultLat, defaultLng], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(this.map);

        // Clique no mapa para definir destino manualmente
        this.map.on('click', (e) => {
            if (this.status === 'ativo') {
                UI.showToast('Para mudar o destino, pause ou cancele o trajeto atual.', 'warning');
                return;
            }
            this.setDestination(e.latlng.lat, e.latlng.lng, `Ponto marcado no mapa (${e.latlng.lat.toFixed(4)}, ${e.latlng.lng.toFixed(4)})`);
        });
    },

    /**
     * Carrega valores iniciais dos formulários/sessão
     */
    initDefaultValues() {
        const radiusInput = document.getElementById('radiusInput');
        const radiusDisplay = document.getElementById('radiusDisplay');
        const alarmTypeSelect = document.getElementById('alarmTypeSelect');
        const soundSelect = document.getElementById('soundSelect');

        if (radiusInput) {
            this.radiusMeters = parseInt(radiusInput.value, 10) || 300;
            if (radiusDisplay) radiusDisplay.textContent = this.radiusMeters + 'm';
        }
        if (alarmTypeSelect) this.alarmType = alarmTypeSelect.value || 'ambos';
        if (soundSelect) this.alarmSound = soundSelect.value || 'sirene';

        // Verifica se há parâmetros salvos em sessionStorage para repetir trajeto
        if (sessionStorage.getItem('prefill_lat')) {
            const lat = parseFloat(sessionStorage.getItem('prefill_lat'));
            const lng = parseFloat(sessionStorage.getItem('prefill_lng'));
            const nome = sessionStorage.getItem('prefill_nome') || 'Destino anterior';
            const raio = parseInt(sessionStorage.getItem('prefill_raio'), 10);

            sessionStorage.removeItem('prefill_lat');
            sessionStorage.removeItem('prefill_lng');
            sessionStorage.removeItem('prefill_nome');
            sessionStorage.removeItem('prefill_raio');

            if (raio && radiusInput) {
                this.radiusMeters = raio;
                radiusInput.value = raio;
                if (radiusDisplay) radiusDisplay.textContent = raio + 'm';
                const radiusLabel = document.getElementById('radiusLabel');
                if (radiusLabel) radiusLabel.textContent = raio + ' metros';
            }

            setTimeout(() => {
                this.setDestination(lat, lng, nome);
            }, 400);
        }
    },

    /**
     * Solicita permissão para notificações em segundo plano
     */
    requestNotificationPermission() {
        if ("Notification" in window && Notification.permission === "default") {
            Notification.requestPermission();
        }
    },

    /**
     * Ativa Wake Lock para manter a tela ligada durante a viagem
     */
    async requestWakeLock() {
        if ('wakeLock' in navigator) {
            try {
                this.wakeLock = await navigator.wakeLock.request('screen');
                this.wakeLock.addEventListener('release', () => {
                    this.wakeLock = null;
                });
            } catch (err) {
                console.warn('Wake Lock não pôde ser ativado:', err);
            }
        }
    },

    releaseWakeLock() {
        if (this.wakeLock) {
            this.wakeLock.release().catch(() => {});
            this.wakeLock = null;
        }
    },

    /**
     * Inicia monitoramento de GPS (RN-02)
     */
    startGpsTracking(isActiveMonitoring = false) {
        if (!("geolocation" in navigator)) {
            UI.showGpsErrorModal("Seu navegador ou dispositivo não possui suporte a GPS.");
            return;
        }

        const options = {
            enableHighAccuracy: true,
            maximumAge: 1000,
            timeout: 12000
        };

        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
        }

        this.watchId = navigator.geolocation.watchPosition(
            (pos) => this.handleGpsSuccess(pos, isActiveMonitoring),
            (err) => this.handleGpsError(err),
            options
        );
    },

    /**
     * Callback de sucesso do GPS
     */
    handleGpsSuccess(position, isActiveMonitoring) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const accuracy = position.coords.accuracy;

        this.currentLocation = { lat, lng, accuracy };

        // Atualiza marcador do usuário no mapa
        this.updateUserMarker(lat, lng);

        // Se for monitoramento ativo e temos destino, calcular distância (RF05, RF06)
        if (this.status === 'ativo' && this.destination) {
            this.processDistanceCheck();
        }
    },

    /**
     * Callback de erro de GPS (RN-06)
     */
    handleGpsError(error) {
        let msg = "Não foi possível obter sua localização.";
        switch (error.code) {
            case error.PERMISSION_DENIED:
                msg = "A permissão de localização foi negada. O alarme precisa do GPS para funcionar.";
                break;
            case error.POSITION_UNAVAILABLE:
                msg = "O sinal do GPS está indisponível no momento. Verifique se o GPS está ativado.";
                break;
            case error.TIMEOUT:
                msg = "Tempo esgotado ao tentar obter o sinal de GPS.";
                break;
        }

        UI.showToast(msg, 'danger', 'Erro no GPS');
        if (this.status === 'ativo') {
            UI.showGpsErrorModal(msg);
        }
    },

    /**
     * Atualiza o marcador do usuário no mapa Leaflet
     */
    updateUserMarker(lat, lng) {
        if (!this.map) return;

        const userIcon = L.divIcon({
            className: 'custom-user-pin',
            html: `
                <div style="background-color: #0d6efd; width: 22px; height: 22px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(13,110,253,0.7); position: relative;">
                    <div style="position: absolute; top: -5px; left: -5px; width: 26px; height: 26px; border-radius: 50%; border: 2px solid #0d6efd; animation: radar-ping 1.5s infinite;"></div>
                </div>
            `,
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });

        if (!this.userMarker) {
            this.userMarker = L.marker([lat, lng], { icon: userIcon }).addTo(this.map);
            this.userMarker.bindPopup('<b>Você está aqui</b>');
            this.map.setView([lat, lng], 15);
        } else {
            this.userMarker.setLatLng([lat, lng]);
        }
    },

    /**
     * Define o destino e desenha o raio no mapa (RN-01, RF02, RF03)
     */
    setDestination(lat, lng, name) {
        this.destination = { lat, lng, name };

        const destInput = document.getElementById('destinationInput');
        if (destInput) destInput.value = name;

        // Atualiza marcador de destino
        if (this.destMarker) {
            this.map.removeLayer(this.destMarker);
        }

        const destIcon = L.divIcon({
            className: 'custom-dest-pin',
            html: `<div style="background-color: #dc3545; color: white; width: 30px; height: 30px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.3);"><i class="bi bi-flag-fill" style="transform: rotate(45deg); font-size: 13px;"></i></div>`,
            iconSize: [30, 30],
            iconAnchor: [15, 30]
        });

        this.destMarker = L.marker([lat, lng], { icon: destIcon }).addTo(this.map);
        this.destMarker.bindPopup(`<b>Destino:</b><br>${name}`).openPopup();

        // Desenha raio de alerta em volta do destino
        this.updateRadiusCircle();

        // Ajusta visualização do mapa para mostrar ambos os pontos
        if (this.currentLocation) {
            const bounds = L.latLngBounds([
                [this.currentLocation.lat, this.currentLocation.lng],
                [lat, lng]
            ]);
            this.map.fitBounds(bounds, { padding: [50, 50] });
            this.processDistanceCheck();
        } else {
            this.map.setView([lat, lng], 15);
        }

        UI.showToast(`Destino definido: ${name}`, 'info');
    },

    /**
     * Atualiza o círculo do raio configurado no mapa
     */
    updateRadiusCircle() {
        if (!this.destination) return;

        if (this.radiusCircle) {
            this.map.removeLayer(this.radiusCircle);
        }

        this.radiusCircle = L.circle([this.destination.lat, this.destination.lng], {
            radius: this.radiusMeters,
            color: '#dc3545',
            fillColor: '#dc3545',
            fillOpacity: 0.18,
            weight: 2,
            dashArray: '6, 6'
        }).addTo(this.map);
    },

    /**
     * Calcula distância usando Fórmula de Haversine em tempo real (RF05)
     */
    calcularDistancia(lat1, lon1, lat2, lon2) {
        const R = 6371000; // Raio da Terra em metros
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    },

    /**
     * Processa checagem contínua de distância (RF05, RF06)
     */
    processDistanceCheck() {
        if (!this.currentLocation || !this.destination) return;

        const distMetros = this.calcularDistancia(
            this.currentLocation.lat,
            this.currentLocation.lng,
            this.destination.lat,
            this.destination.lng
        );

        this.updateDistanceUI(distMetros);

        // Verifica se entrou no raio de alerta (RF06, RF07)
        if (this.status === 'ativo' && distMetros <= this.radiusMeters && !this.alarmTriggered) {
            this.triggerAlarm(distMetros);
        }
    },

    /**
     * Atualiza a interface gráfica com a distância atual
     */
    updateDistanceUI(distMetros) {
        const distEl = document.getElementById('currentDistanceValue');
        const unitEl = document.getElementById('currentDistanceUnit');
        const infoEl = document.getElementById('distanceSubtext');

        if (!distEl || !unitEl) return;

        if (distMetros >= 1000) {
            distEl.textContent = (distMetros / 1000).toFixed(2);
            unitEl.textContent = 'km';
        } else {
            distEl.textContent = Math.round(distMetros);
            unitEl.textContent = 'metros';
        }

        if (infoEl) {
            if (distMetros <= this.radiusMeters) {
                infoEl.innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> DENTRO DO RAIO DE ALERTA (${this.radiusMeters}m)!</span>`;
            } else {
                const restante = Math.round(distMetros - this.radiusMeters);
                infoEl.textContent = `Faltam aproximadamente ${restante > 1000 ? (restante/1000).toFixed(2) + ' km' : restante + ' m'} para entrar no raio de aviso.`;
            }
        }
    },

    /**
     * Aciona o alarme imediatamente (RN-04, RF07, RF08)
     */
    triggerAlarm(distMetros) {
        this.alarmTriggered = true;
        this.status = 'acionado';
        this.updateStatusUI();

        // Dispara alarme sonoro e/ou vibração contínua
        AlarmController.start(this.alarmType, this.alarmSound);

        // Notificação do Navegador (se em background)
        if ("Notification" in window && Notification.permission === "granted") {
            try {
                new Notification("ALERTA DE CHEGADA!", {
                    body: `Você está a apenas ${Math.round(distMetros)}m do destino: ${this.destination.name}. Desembarque agora!`,
                    icon: "https://cdn-icons-png.flaticon.com/512/565/565422.png",
                    requireInteraction: true
                });
            } catch (e) {}
        }

        // Registra acionamento no backend
        if (this.tripId) {
            fetch('api/trajeto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'acionar', viagem_id: this.tripId })
            }).catch(() => {});
        }

        // Exibe overlay/tela de alarme de alta urgência com botão manual de desligamento
        const overlay = document.getElementById('alarmOverlay');
        if (overlay) {
            document.getElementById('alarmDestinoNome').textContent = this.destination.name;
            document.getElementById('alarmDistanciaAtual').textContent = `${Math.round(distMetros)} metros`;
            overlay.classList.remove('d-none');
        }
    },

    /**
     * Desliga o alarme manualmente pelo usuário (RF08, RF09)
     */
    stopAlarmAndTrip() {
        AlarmController.stop();

        const overlay = document.getElementById('alarmOverlay');
        if (overlay) {
            overlay.classList.add('d-none');
        }

        const viagemIdParaConcluir = this.tripId;
        this.alarmTriggered = false;
        this.status = 'inativo';
        this.tripId = null;
        this.releaseWakeLock();
        this.updateStatusUI();

        if (viagemIdParaConcluir) {
            fetch('api/trajeto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'concluir', viagem_id: viagemIdParaConcluir })
            }).catch(() => {});
        }

        UI.showToast('Alarme desligado e monitoramento finalizado com sucesso!', 'success', 'Chegou ao Destino');
    },

    /**
     * Inicia o monitoramento (RN-05, RF01)
     */
    async startTrip() {
        if (!this.destination) {
            UI.showToast('Informe um endereço de destino válido para iniciar o trajeto.', 'warning');
            return;
        }

        if (!this.currentLocation) {
            UI.showToast('Aguardando sinal de GPS... Por favor, verifique se a localização está permitida.', 'warning');
            this.startGpsTracking(true);
            return;
        }

        // Pré-inicializa o áudio para desbloquear autoplay
        AlarmController.initAudioContext();
        await this.requestWakeLock();

        // Registra a viagem no backend via API
        try {
            const resp = await fetch('api/trajeto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'iniciar',
                    destino_nome: this.destination.name,
                    destino_lat: this.destination.lat,
                    destino_lng: this.destination.lng,
                    raio_metros: this.radiusMeters,
                    tipo_alarme: this.alarmType,
                    som_alarme: this.alarmSound
                })
            });
            const data = await resp.json();
            if (data.success) {
                this.tripId = data.data.viagem_id;
            }
        } catch (e) {
            console.warn('Erro ao salvar no backend, continuando monitoramento local:', e);
        }

        this.status = 'ativo';
        this.alarmTriggered = false;
        this.startGpsTracking(true);
        this.updateStatusUI();
        this.processDistanceCheck();

        UI.showToast('Monitoramento iniciado em tempo real! Não se preocupe, avisaremos quando estiver perto.', 'success', 'Trajeto Iniciado');
    },

    /**
     * Pausa o monitoramento (RN-05)
     */
    pauseTrip() {
        if (this.status !== 'ativo') return;
        this.status = 'pausado';
        this.updateStatusUI();

        if (this.tripId) {
            fetch('api/trajeto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'pausar', viagem_id: this.tripId })
            }).catch(() => {});
        }

        UI.showToast('Monitoramento pausado temporariamente.', 'info');
    },

    /**
     * Retoma o monitoramento (RN-05)
     */
    resumeTrip() {
        if (this.status !== 'pausado') return;
        this.status = 'ativo';
        this.updateStatusUI();

        if (this.tripId) {
            fetch('api/trajeto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'retomar', viagem_id: this.tripId })
            }).catch(() => {});
        }

        this.processDistanceCheck();
        UI.showToast('Monitoramento retomado!', 'success');
    },

    /**
     * Cancela o trajeto (RN-05)
     */
    cancelTrip() {
        UI.confirmAction(
            'Cancelar Trajeto',
            'Deseja realmente cancelar o monitoramento do trajeto atual?',
            () => {
                AlarmController.stop();
                this.releaseWakeLock();

                const id = this.tripId;
                this.status = 'inativo';
                this.alarmTriggered = false;
                this.tripId = null;
                this.updateStatusUI();

                if (id) {
                    fetch('api/trajeto.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'cancelar', viagem_id: id })
                    }).catch(() => {});
                }

                UI.showToast('Monitoramento do trajeto cancelado.', 'warning');
            }
        );
    },

    /**
     * Atualiza a interface de botões e status
     */
    updateStatusUI() {
        const badge = document.getElementById('statusBadge');
        const btnStart = document.getElementById('btnStartTrip');
        const btnPause = document.getElementById('btnPauseTrip');
        const btnResume = document.getElementById('btnResumeTrip');
        const btnCancel = document.getElementById('btnCancelTrip');
        const configPanel = document.getElementById('configPanelInputs');

        if (!badge) return;

        badge.className = `status-badge-tracker ${this.status}`;

        if (this.status === 'inativo') {
            badge.innerHTML = `<span class="badge bg-secondary me-1">Parado</span> Monitoramento inativo`;
            if (btnStart) btnStart.classList.remove('d-none');
            if (btnPause) btnPause.classList.add('d-none');
            if (btnResume) btnResume.classList.add('d-none');
            if (btnCancel) btnCancel.classList.add('d-none');
            if (configPanel) configPanel.querySelectorAll('input, select').forEach(el => el.disabled = false);
        } else if (this.status === 'ativo') {
            badge.innerHTML = `<span class="gps-pulse-dot"></span> Monitorando em tempo real...`;
            if (btnStart) btnStart.classList.add('d-none');
            if (btnPause) btnPause.classList.remove('d-none');
            if (btnResume) btnResume.classList.add('d-none');
            if (btnCancel) btnCancel.classList.remove('d-none');
            if (configPanel) configPanel.querySelectorAll('input, select').forEach(el => el.disabled = true);
        } else if (this.status === 'pausado') {
            badge.innerHTML = `<span class="badge bg-warning text-dark me-1">Pausado</span> Monitoramento suspenso`;
            if (btnStart) btnStart.classList.add('d-none');
            if (btnPause) btnPause.classList.add('d-none');
            if (btnResume) btnResume.classList.remove('d-none');
            if (btnCancel) btnCancel.classList.remove('d-none');
        } else if (this.status === 'acionado') {
            badge.innerHTML = `<span class="badge bg-danger me-1">ALARME ATIVO</span> No destino!`;
        }
    },

    /**
     * Busca endereço usando OpenStreetMap / Nominatim (RN-01, RF02)
     */
    searchAddress(query) {
        if (!query || query.length < 3) return;

        const suggestionsBox = document.getElementById('searchSuggestions');
        if (!suggestionsBox) return;

        suggestionsBox.innerHTML = '<div class="p-2 text-muted small"><i class="bi bi-hourglass-split"></i> Buscando endereços...</div>';
        suggestionsBox.classList.remove('d-none');

        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&addressdetails=1`;

        fetch(url, {
            headers: { 'Accept-Language': 'pt-BR,pt;q=0.9' }
        })
        .then(res => res.json())
        .then(results => {
            if (!results || results.length === 0) {
                suggestionsBox.innerHTML = '<div class="p-2 text-muted small">Nenhum endereço encontrado. Tente clicar no mapa.</div>';
                return;
            }

            suggestionsBox.innerHTML = '';
            results.forEach(item => {
                const div = document.createElement('div');
                div.className = 'autocomplete-item';
                div.innerHTML = `<i class="bi bi-geo-alt text-primary me-2"></i> ${item.display_name}`;
                div.addEventListener('click', () => {
                    this.setDestination(parseFloat(item.lat), parseFloat(item.lon), item.display_name);
                    suggestionsBox.classList.add('d-none');
                });
                suggestionsBox.appendChild(div);
            });
        })
        .catch(err => {
            console.warn('Erro na busca de endereço:', err);
            suggestionsBox.classList.add('d-none');
        });
    },

    /**
     * Vincula eventos da interface
     */
    bindEvents() {
        // Slider e input de Raio (RN-03, RF03)
        const radiusInput = document.getElementById('radiusInput');
        const radiusDisplay = document.getElementById('radiusDisplay');
        if (radiusInput) {
            radiusInput.addEventListener('input', (e) => {
                this.radiusMeters = parseInt(e.target.value, 10);
                if (radiusDisplay) radiusDisplay.textContent = this.radiusMeters + 'm';
                this.updateRadiusCircle();
                if (this.currentLocation && this.destination) {
                    this.processDistanceCheck();
                }
            });
        }

        // Botões de raio rápido (ex: 200m, 500m, 1km)
        document.querySelectorAll('.btn-quick-radius').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const val = parseInt(e.currentTarget.getAttribute('data-radius'), 10);
                if (radiusInput) radiusInput.value = val;
                this.radiusMeters = val;
                if (radiusDisplay) radiusDisplay.textContent = val + 'm';
                this.updateRadiusCircle();
                if (this.currentLocation && this.destination) {
                    this.processDistanceCheck();
                }
            });
        });

        // Tipo de alarme e som (RF04)
        const alarmTypeSelect = document.getElementById('alarmTypeSelect');
        if (alarmTypeSelect) {
            alarmTypeSelect.addEventListener('change', (e) => {
                this.alarmType = e.target.value;
            });
        }

        const soundSelect = document.getElementById('soundSelect');
        if (soundSelect) {
            soundSelect.addEventListener('change', (e) => {
                this.alarmSound = e.target.value;
            });
        }

        // Testar alarme
        const btnTestAlarm = document.getElementById('btnTestAlarm');
        if (btnTestAlarm) {
            btnTestAlarm.addEventListener('click', () => {
                AlarmController.testPreview(this.alarmType, this.alarmSound);
                UI.showToast(`Testando alerta (${this.alarmType} - ${this.alarmSound})...`, 'info');
            });
        }

        // Botão de localização atual (centralizar no usuário)
        const btnCenterUser = document.getElementById('btnCenterUser');
        if (btnCenterUser) {
            btnCenterUser.addEventListener('click', () => {
                if (this.currentLocation && this.map) {
                    this.map.setView([this.currentLocation.lat, this.currentLocation.lng], 16);
                } else {
                    this.startGpsTracking(true);
                }
            });
        }

        // Campo de busca de destino
        const destInput = document.getElementById('destinationInput');
        const suggestionsBox = document.getElementById('searchSuggestions');
        let debounceTimer = null;

        if (destInput) {
            destInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                const query = e.target.value.trim();
                if (query.length >= 3) {
                    debounceTimer = setTimeout(() => this.searchAddress(query), 500);
                } else if (suggestionsBox) {
                    suggestionsBox.classList.add('d-none');
                }
            });

            // Fecha sugestões ao clicar fora
            document.addEventListener('click', (e) => {
                if (suggestionsBox && !destInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                    suggestionsBox.classList.add('d-none');
                }
            });
        }

        // Botões de controle de trajeto (RN-05)
        const btnStart = document.getElementById('btnStartTrip');
        if (btnStart) btnStart.addEventListener('click', () => this.startTrip());

        const btnPause = document.getElementById('btnPauseTrip');
        if (btnPause) btnPause.addEventListener('click', () => this.pauseTrip());

        const btnResume = document.getElementById('btnResumeTrip');
        if (btnResume) btnResume.addEventListener('click', () => this.resumeTrip());

        const btnCancel = document.getElementById('btnCancelTrip');
        if (btnCancel) btnCancel.addEventListener('click', () => this.cancelTrip());

        // Botão de Desligar Alarme (Overlay de Chegada - RF09)
        const btnStopAlarm = document.getElementById('btnStopAlarm');
        if (btnStopAlarm) btnStopAlarm.addEventListener('click', () => this.stopAlarmAndTrip());
    }
};

// Inicializa a aplicação quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
