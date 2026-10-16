<?php
/**
 * Salvar Funcionário / Barbeiro - Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/funcionarios.php');
    exit;
}

$id            = (int)($_POST['id'] ?? 0);
$nome          = trim($_POST['nome'] ?? '');
$email         = trim($_POST['email'] ?? '');
$cpf           = trim($_POST['cpf'] ?? '');
$telefone      = trim($_POST['telefone'] ?? '');
$senha         = trim($_POST['senha'] ?? '');
$especialidade = trim($_POST['especialidade'] ?? '');
$chavePix      = trim($_POST['chave_pix'] ?? '');
$tipoChavePix  = trim($_POST['tipo_chave_pix'] ?? 'E-mail');
$comissao      = (float)($_POST['comissao_percentual'] ?? 50.0);
$perfil        = trim($_POST['perfil'] ?? 'barbeiro');

if (empty($nome) || empty($email) || empty($cpf)) {
    setMensagemToast('warning', 'Campos Obrigatórios', 'Nome, E-mail e CPF são obrigatórios.');
    header('Location: ' . BASE_URL . '/admin/funcionarios.php');
    exit;
}

try {
    $pdo = getDBConnection();

    if ($id > 0) {
        // Atualizar funcionário existente
        if (!empty($senha)) {
            $sql = "
                UPDATE usuarios 
                SET nome = :nome, email = :email, cpf = :cpf, telefone = :telefone,
                    senha = :senha, especialidade = :especialidade, chave_pix = :chave_pix,
                    tipo_chave_pix = :tipo_chave_pix, comissao_percentual = :comissao, perfil = :perfil
                WHERE id = :id
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome'          => $nome,
                ':email'         => $email,
                ':cpf'           => $cpf,
                ':telefone'      => $telefone,
                ':senha'         => password_hash($senha, PASSWORD_DEFAULT),
                ':especialidade' => $especialidade,
                ':chave_pix'     => $chavePix,
                ':tipo_chave_pix'=> $tipoChavePix,
                ':comissao'      => $comissao,
                ':perfil'        => $perfil,
                ':id'            => $id
            ]);
        } else {
            $sql = "
                UPDATE usuarios 
                SET nome = :nome, email = :email, cpf = :cpf, telefone = :telefone,
                    especialidade = :especialidade, chave_pix = :chave_pix,
                    tipo_chave_pix = :tipo_chave_pix, comissao_percentual = :comissao, perfil = :perfil
                WHERE id = :id
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome'          => $nome,
                ':email'         => $email,
                ':cpf'           => $cpf,
                ':telefone'      => $telefone,
                ':especialidade' => $especialidade,
                ':chave_pix'     => $chavePix,
                ':tipo_chave_pix'=> $tipoChavePix,
                ':comissao'      => $comissao,
                ':perfil'        => $perfil,
                ':id'            => $id
            ]);
        }
        setMensagemToast('success', 'Atualizado com Sucesso!', 'Dados do colaborador foram atualizados.');
    } else {
        // Criar novo funcionário
        $senhaPadrao = !empty($senha) ? $senha : '123456';
        $sql = "
            INSERT INTO usuarios (nome, email, cpf, telefone, senha, perfil, especialidade, chave_pix, tipo_chave_pix, comissao_percentual, ativo)
            VALUES (:nome, :email, :cpf, :telefone, :senha, :perfil, :especialidade, :chave_pix, :tipo_chave_pix, :comissao, 1)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome'          => $nome,
            ':email'         => $email,
            ':cpf'           => $cpf,
            ':telefone'      => $telefone,
            ':senha'         => password_hash($senhaPadrao, PASSWORD_DEFAULT),
            ':perfil'        => $perfil,
            ':especialidade' => $especialidade,
            ':chave_pix'     => $chavePix,
            ':tipo_chave_pix'=> $tipoChavePix,
            ':comissao'      => $comissao
        ]);
        setMensagemToast('success', 'Colaborador Cadastrado!', 'Novo funcionário cadastrado com sucesso. Senha inicial configurada.');
    }

    header('Location: ' . BASE_URL . '/admin/funcionarios.php');
    exit;

} catch (Exception $e) {
    setMensagemToast('danger', 'Erro ao Salvar', 'Não foi possível salvar o colaborador: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/admin/funcionarios.php');
    exit;
}
