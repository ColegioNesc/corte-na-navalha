/**
 * Módulo de Alarme Sonoro e Vibração (Web Audio API & Vibration API)
 * Totalmente autônomo, sem dependência de arquivos externos que possam falhar.
 */

const AlarmController = {
    audioCtx: null,
    isPlaying: false,
    vibrationInterval: null,
    currentOscillators: [],
    timerLoop: null,

    /**
     * Inicializa o contexto de áudio após interação do usuário
     */
    initAudioContext() {
        if (!this.audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            this.audioCtx = new AudioContext();
        }
        if (this.audioCtx.state === 'suspended') {
            this.audioCtx.resume();
        }
    },

    /**
     * Toca o som de sirene contínua
     */
    playSirenTone() {
        if (!this.audioCtx || !this.isPlaying) return;

        const osc = this.audioCtx.createOscillator();
        const gain = this.audioCtx.createGain();

        osc.type = 'sawtooth';
        const now = this.audioCtx.currentTime;

        // Modulação de frequência de sirene
        osc.frequency.setValueAtTime(600, now);
        osc.frequency.linearRampToValueAtTime(950, now + 0.35);
        osc.frequency.linearRampToValueAtTime(600, now + 0.7);

        gain.gain.setValueAtTime(0.3, now);
        gain.gain.linearRampToValueAtTime(0.3, now + 0.65);
        gain.gain.linearRampToValueAtTime(0.01, now + 0.7);

        osc.connect(gain);
        gain.connect(this.audioCtx.destination);

        osc.start(now);
        osc.stop(now + 0.7);

        this.currentOscillators.push(osc);
    },

    /**
     * Toca bip urgente
     */
    playBeepTone() {
        if (!this.audioCtx || !this.isPlaying) return;

        const osc = this.audioCtx.createOscillator();
        const gain = this.audioCtx.createGain();

        osc.type = 'square';
        const now = this.audioCtx.currentTime;

        osc.frequency.setValueAtTime(1100, now);
        gain.gain.setValueAtTime(0.25, now);
        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.25);

        osc.connect(gain);
        gain.connect(this.audioCtx.destination);

        osc.start(now);
        osc.stop(now + 0.25);

        this.currentOscillators.push(osc);
    },

    /**
     * Toca chime melódico
     */
    playChimeTone() {
        if (!this.audioCtx || !this.isPlaying) return;

        const freqs = [523.25, 659.25, 783.99, 1046.50]; // C5, E5, G5, C6
        freqs.forEach((freq, index) => {
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();

            osc.type = 'sine';
            const now = this.audioCtx.currentTime + (index * 0.12);

            osc.frequency.setValueAtTime(freq, now);
            gain.gain.setValueAtTime(0.2, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.4);

            osc.connect(gain);
            gain.connect(this.audioCtx.destination);

            osc.start(now);
            osc.stop(now + 0.4);

            this.currentOscillators.push(osc);
        });
    },

    /**
     * Executa vibração contínua em aparelhos compatíveis
     */
    startVibration() {
        if (!("vibrate" in navigator)) return;

        // Padrão: vibra 700ms, pausa 300ms, vibra 700ms
        const pattern = [700, 300, 700, 300, 1000, 400];
        try {
            navigator.vibrate(pattern);
            this.vibrationInterval = setInterval(() => {
                if (this.isPlaying) {
                    navigator.vibrate(pattern);
                } else {
                    this.stopVibration();
                }
            }, 3500);
        } catch (e) {
            console.warn('Erro ao disparar vibração:', e);
        }
    },

    stopVibration() {
        if (this.vibrationInterval) {
            clearInterval(this.vibrationInterval);
            this.vibrationInterval = null;
        }
        if ("vibrate" in navigator) {
            try {
                navigator.vibrate(0);
            } catch (e) {}
        }
    },

    /**
     * Inicia o alarme contínuo (som, vibração ou ambos)
     * Mantém ativo até que o usuário desligue manualmente (RF08)
     */
    start(tipo = 'ambos', som = 'sirene') {
        if (this.isPlaying) return;
        this.isPlaying = true;
        this.initAudioContext();

        const tocarSom = (tipo === 'som' || tipo === 'ambos');
        const vibrar = (tipo === 'vibracao' || tipo === 'ambos');

        if (vibrar) {
            this.startVibration();
        }

        if (tocarSom) {
            const loopInterval = (som === 'sirene') ? 750 : (som === 'beep' ? 400 : 900);
            const playSound = () => {
                if (!this.isPlaying) return;
                if (som === 'sirene') {
                    this.playSirenTone();
                } else if (som === 'beep') {
                    this.playBeepTone();
                } else {
                    this.playChimeTone();
                }
            };

            playSound();
            this.timerLoop = setInterval(playSound, loopInterval);
        }
    },

    /**
     * Desliga completamente o alarme (RF09)
     */
    stop() {
        this.isPlaying = false;

        if (this.timerLoop) {
            clearInterval(this.timerLoop);
            this.timerLoop = null;
        }

        this.stopVibration();

        // Para osciladores pendentes
        this.currentOscillators.forEach(osc => {
            try {
                osc.stop();
                osc.disconnect();
            } catch (e) {}
        });
        this.currentOscillators = [];
    },

    /**
     * Teste prévio de 2.5 segundos das configurações de som/vibração
     */
    testPreview(tipo = 'ambos', som = 'sirene') {
        this.start(tipo, som);
        setTimeout(() => {
            this.stop();
        }, 2500);
    }
};
