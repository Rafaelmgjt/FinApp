<?php
// ============================================
// FUNÇÕES DE BANCO DE DADOS (CSV)
// ============================================

/**
 * Inicializa os arquivos CSV se não existirem
 */
function inicializarCSVs() {
    // Usuários
    if (!file_exists(USERS_FILE)) {
        $header = "id,nome,email,senha,data_criacao,ultimo_acesso\n";
        file_put_contents(USERS_FILE, $header);
    }
    
    // Contas
    if (!file_exists(CONTAS_FILE)) {
        $header = "id,usuario_id,nome,descricao,saldo_inicial,saldo_atual,data_criacao\n";
        file_put_contents(CONTAS_FILE, $header);
    }
    
    // Movimentações
    if (!file_exists(MOVIMENTACOES_FILE)) {
        $header = "id,usuario_id,conta_id,tipo,descricao,valor,data_movimentacao,data_criacao,status,data_confirmacao,transferencia_id\n";
        file_put_contents(MOVIMENTACOES_FILE, $header);
    }
    
    // Transferências
    if (!file_exists(TRANSFERENCIAS_FILE)) {
        $header = "id,usuario_origem_id,usuario_destino_id,valor,descricao,data_transferencia,data_criacao\n";
        file_put_contents(TRANSFERENCIAS_FILE, $header);
    }
}

// Inicializa os CSVs
inicializarCSVs();

/**
 * Lê um arquivo CSV e retorna um array
 * @param string $arquivo Caminho do arquivo
 * @return array Array com os dados
 */
function lerCSV($arquivo) {
    $dados = [];
    
    if (!file_exists($arquivo)) {
        return $dados;
    }
    
    $arquivo_aberto = fopen($arquivo, 'r');
    
    if (!$arquivo_aberto) {
        return $dados;
    }
    
    $cabecalho = fgetcsv($arquivo_aberto);
    
    while (($linha = fgetcsv($arquivo_aberto)) !== false) {
        if (count($linha) > 1 || trim($linha[0]) !== '') {
            $registro = [];
            foreach ($cabecalho as $indice => $coluna) {
                $registro[$coluna] = isset($linha[$indice]) ? trim($linha[$indice]) : '';
            }
            if (!array_key_exists('transferencia_id', $registro)) {
                $registro['transferencia_id'] = '';
            }
            $dados[] = $registro;
        }
    }
    
    fclose($arquivo_aberto);
    return $dados;
}

/**
 * Escreve dados em um arquivo CSV
 * @param string $arquivo Caminho do arquivo
 * @param array $dados Array de dados
 */
function escreverCSV($arquivo, $dados) {
    $arquivo_aberto = fopen($arquivo, 'w');
    
    if (!$arquivo_aberto) {
        return false;
    }
    
    if (!empty($dados)) {
        $cabecalho = array_keys($dados[0]);
        fputcsv($arquivo_aberto, $cabecalho);
        
        foreach ($dados as $linha) {
            fputcsv($arquivo_aberto, $linha);
        }
    }
    
    fclose($arquivo_aberto);
    return true;
}

/**
 * Gera um novo ID para um registro
 * @param array $dados Array de dados existentes
 * @return int Novo ID
 */
function gerarNovoId($dados) {
    if (empty($dados)) {
        return 1;
    }
    
    $ids = array_column($dados, 'id');
    return max($ids) + 1;
}

/**
 * Busca um usuário por ID
 * @param int $usuario_id ID do usuário
 * @return array|null Dados do usuário ou null
 */
function buscarUsuarioPorId($usuario_id) {
    $usuarios = lerCSV(USERS_FILE);
    
    foreach ($usuarios as $usuario) {
        if ($usuario['id'] == $usuario_id) {
            return $usuario;
        }
    }
    
    return null;
}

/**
 * Busca um usuário por email
 * @param string $email Email do usuário
 * @return array|null Dados do usuário ou null
 */
function buscarUsuarioPorEmail($email) {
    $usuarios = lerCSV(USERS_FILE);
    
    foreach ($usuarios as $usuario) {
        if ($usuario['email'] === $email) {
            return $usuario;
        }
    }
    
    return null;
}

/**
 * Cria um novo usuário
 * @param string $nome Nome do usuário
 * @param string $email Email do usuário
 * @param string $senha Senha (será hasheada)
 * @return array Dados do usuário criado
 */
function criarUsuario($nome, $email, $senha) {
    $usuarios = lerCSV(USERS_FILE);
    
    if (buscarUsuarioPorEmail($email)) {
        return ['erro' => 'Email já cadastrado'];
    }
    
    $novo_id = gerarNovoId($usuarios);
    
    $usuario = [
        'id' => $novo_id,
        'nome' => $nome,
        'email' => $email,
        'senha' => password_hash($senha, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS),
        'data_criacao' => date('Y-m-d H:i:s'),
        'ultimo_acesso' => ''
    ];
    
    $usuarios[] = $usuario;
    escreverCSV(USERS_FILE, $usuarios);
    
    // Cria conta padrão
    criarConta($novo_id, 'Conta Principal', 'Sua conta principal de transações');
    
    return $usuario;
}

/**
 * Autentica um usuário
 * @param string $email Email do usuário
 * @param string $senha Senha do usuário
 * @return array|null Dados do usuário ou null se inválido
 */
function autenticarUsuario($email, $senha) {
    $usuario = buscarUsuarioPorEmail($email);
    
    if (!$usuario) {
        return null;
    }
    
    if (!password_verify($senha, $usuario['senha'])) {
        return null;
    }
    
    // Atualiza último acesso
    $usuarios = lerCSV(USERS_FILE);
    foreach ($usuarios as &$u) {
        if ($u['id'] == $usuario['id']) {
            $u['ultimo_acesso'] = date('Y-m-d H:i:s');
            break;
        }
    }
    escreverCSV(USERS_FILE, $usuarios);
    
    return $usuario;
}

/**
 * Cria uma nova conta
 * @param int $usuario_id ID do usuário
 * @param string $nome Nome da conta
 * @param string $descricao Descrição da conta
 * @param float $saldo_inicial Saldo inicial (padrão 0)
 * @return array Dados da conta criada
 */
function criarConta($usuario_id, $nome, $descricao, $saldo_inicial = 0) {
    $contas = lerCSV(CONTAS_FILE);
    
    $novo_id = gerarNovoId($contas);
    
    $conta = [
        'id' => $novo_id,
        'usuario_id' => $usuario_id,
        'nome' => $nome,
        'descricao' => $descricao,
        'saldo_inicial' => number_format($saldo_inicial, 2, '.', ''),
        'saldo_atual' => number_format($saldo_inicial, 2, '.', ''),
        'data_criacao' => date('Y-m-d H:i:s')
    ];
    
    $contas[] = $conta;
    escreverCSV(CONTAS_FILE, $contas);
    
    return $conta;
}

/**
 * Busca todas as contas de um usuário
 * @param int $usuario_id ID do usuário
 * @return array Array de contas
 */
function buscarContasUsuario($usuario_id) {
    $contas = lerCSV(CONTAS_FILE);
    $contas_usuario = [];
    
    foreach ($contas as $conta) {
        if ($conta['usuario_id'] == $usuario_id) {
            $contas_usuario[] = $conta;
        }
    }
    
    return $contas_usuario;
}

/**
 * Busca uma conta por ID
 * @param int $conta_id ID da conta
 * @return array|null Dados da conta ou null
 */
function buscarContaPorId($conta_id) {
    $contas = lerCSV(CONTAS_FILE);
    
    foreach ($contas as $conta) {
        if ($conta['id'] == $conta_id) {
            return $conta;
        }
    }
    
    return null;
}

/**
 * Cria uma nova movimentação
 * @param int $usuario_id ID do usuário
 * @param int $conta_id ID da conta
 * @param string $tipo 'receita' ou 'despesa'
 * @param string $descricao Descrição da movimentação
 * @param float $valor Valor da movimentação
 * @param string $data_movimentacao Data da movimentação (YYYY-MM-DD)
 * @param string $status 'pendente' ou 'confirmado'
 * @return array Dados da movimentação criada
 */
function criarMovimentacao($usuario_id, $conta_id, $tipo, $descricao, $valor, $data_movimentacao, $status = 'confirmado', $transferencia_id = '') {
    $movimentacoes = lerCSV(MOVIMENTACOES_FILE);
    
    $novo_id = gerarNovoId($movimentacoes);
    
    $movimentacao = [
        'id' => $novo_id,
        'usuario_id' => $usuario_id,
        'conta_id' => $conta_id,
        'tipo' => strtolower($tipo),
        'descricao' => $descricao,
        'valor' => number_format($valor, 2, '.', ''),
        'data_movimentacao' => $data_movimentacao,
        'data_criacao' => date('Y-m-d H:i:s'),
        'status' => $status,
        'data_confirmacao' => $status === 'confirmado' ? date('Y-m-d H:i:s') : '',
        'transferencia_id' => $transferencia_id
    ];
    
    $movimentacoes[] = $movimentacao;
    escreverCSV(MOVIMENTACOES_FILE, $movimentacoes);
    
    // Atualiza saldo da conta se confirmado
    if ($status === 'confirmado') {
        atualizarSaldoConta($conta_id, $tipo, $valor);
    }
    
    return $movimentacao;
}

/**
 * Busca movimentações de um usuário
 * @param int $usuario_id ID do usuário
 * @param int|null $conta_id ID da conta (opcional)
 * @return array Array de movimentações
 */
function buscarMovimentacoes($usuario_id, $conta_id = null) {
    $movimentacoes = lerCSV(MOVIMENTACOES_FILE);
    $resultado = [];
    
    foreach ($movimentacoes as $mov) {
        if ($mov['usuario_id'] == $usuario_id) {
            if ($conta_id === null || $mov['conta_id'] == $conta_id) {
                $resultado[] = $mov;
            }
        }
    }
    
    return $resultado;
}

/**
 * Busca uma movimentação por ID
 * @param int $movimentacao_id ID da movimentação
 * @return array|null Dados da movimentação ou null
 */
function buscarMovimentacaoPorId($movimentacao_id) {
    $movimentacoes = lerCSV(MOVIMENTACOES_FILE);
    
    foreach ($movimentacoes as $mov) {
        if ($mov['id'] == $movimentacao_id) {
            return $mov;
        }
    }
    
    return null;
}

/**
 * Atualiza o saldo de uma conta
 * @param int $conta_id ID da conta
 * @param string $tipo 'receita' ou 'despesa'
 * @param float $valor Valor da movimentação
 */
function atualizarSaldoConta($conta_id, $tipo, $valor) {
    $contas = lerCSV(CONTAS_FILE);
    
    foreach ($contas as &$conta) {
        if ($conta['id'] == $conta_id) {
            $saldo_atual = (float)$conta['saldo_atual'];
            
            if (strtolower($tipo) === 'receita') {
                $saldo_atual += (float)$valor;
            } else {
                $saldo_atual -= (float)$valor;
            }
            
            $conta['saldo_atual'] = number_format($saldo_atual, 2, '.', '');
            break;
        }
    }
    
    escreverCSV(CONTAS_FILE, $contas);
}

/**
 * Edita uma movimentação
 * @param int $movimentacao_id ID da movimentação
 * @param array $dados Novos dados
 * @return bool Sucesso ou falha
 */
function editarMovimentacao($movimentacao_id, $dados) {
    $movimentacoes = lerCSV(MOVIMENTACOES_FILE);
    $movimentacao_anterior = null;
    
    foreach ($movimentacoes as &$mov) {
        if ($mov['id'] == $movimentacao_id) {
            $movimentacao_anterior = $mov;
            
            // Se o status era confirmado e agora é pendente, desfaz o saldo
            if ($mov['status'] === 'confirmado' && $dados['status'] === 'pendente') {
                atualizarSaldoConta($mov['conta_id'], 
                    ($mov['tipo'] === 'receita' ? 'despesa' : 'receita'), 
                    $mov['valor']);
            }
            
            // Atualiza os dados
            foreach ($dados as $chave => $valor) {
                $mov[$chave] = $valor;
            }
            
            // Se mudou de pendente para confirmado, atualiza saldo
            if ($movimentacao_anterior['status'] === 'pendente' && $mov['status'] === 'confirmado') {
                $mov['data_confirmacao'] = date('Y-m-d H:i:s');
                atualizarSaldoConta($mov['conta_id'], $mov['tipo'], $mov['valor']);
            }
            
            break;
        }
    }
    
    escreverCSV(MOVIMENTACOES_FILE, $movimentacoes);
    return true;
}

/**
 * Deleta uma movimentação
 * @param int $movimentacao_id ID da movimentação
 * @return bool Sucesso ou falha
 */
function deletarMovimentacao($movimentacao_id) {
    $movimentacoes = lerCSV(MOVIMENTACOES_FILE);
    $transferencia_id = '';
    $movimentacoes_para_remover = [];
    $transferencias = lerCSV(TRANSFERENCIAS_FILE);
    
    foreach ($movimentacoes as $mov) {
        if ($mov['id'] == $movimentacao_id) {
            $transferencia_id = $mov['transferencia_id'] ?? '';
            break;
        }
    }

    if (!empty($transferencia_id)) {
        foreach ($movimentacoes as $indice => $mov) {
            if (($mov['transferencia_id'] ?? '') === $transferencia_id) {
                $movimentacoes_para_remover[$indice] = $mov;
            }
        }
    }

    if (empty($movimentacoes_para_remover)) {
        foreach ($movimentacoes as $indice => $mov) {
            if ($mov['id'] == $movimentacao_id) {
                $movimentacoes_para_remover[$indice] = $mov;
                break;
            }
        }
    }

    foreach ($movimentacoes_para_remover as $indice => $mov) {
        if ($mov['status'] === 'confirmado') {
            atualizarSaldoConta($mov['conta_id'],
                ($mov['tipo'] === 'receita' ? 'despesa' : 'receita'),
                $mov['valor']);
        }
        unset($movimentacoes[$indice]);
    }

    if (!empty($transferencia_id)) {
        $transferencias = array_values(array_filter($transferencias, fn($t) => $t['id'] != $transferencia_id));
        escreverCSV(TRANSFERENCIAS_FILE, $transferencias);
    }
    
    $movimentacoes = array_values($movimentacoes);
    escreverCSV(MOVIMENTACOES_FILE, $movimentacoes);
    return true;
}

/**
 * Cria uma transferência entre usuários
 * @param int $usuario_origem_id ID do usuário que envia
 * @param int $usuario_destino_id ID do usuário que recebe
 * @param float $valor Valor da transferência
 * @param string $descricao Descrição da transferência
 * @return array Dados da transferência criada
 */
function criarTransferencia($usuario_origem_id, $usuario_destino_id, $valor, $descricao) {
    $transferencias = lerCSV(TRANSFERENCIAS_FILE);
    
    $novo_id = gerarNovoId($transferencias);
    
    $transferencia = [
        'id' => $novo_id,
        'usuario_origem_id' => $usuario_origem_id,
        'usuario_destino_id' => $usuario_destino_id,
        'valor' => number_format($valor, 2, '.', ''),
        'descricao' => $descricao,
        'data_transferencia' => date('Y-m-d H:i:s'),
        'data_criacao' => date('Y-m-d H:i:s')
    ];
    
    $transferencias[] = $transferencia;
    escreverCSV(TRANSFERENCIAS_FILE, $transferencias);
    
    return $transferencia;
}

/**
 * Cria transferências de usuário para usuário e vincula as movimentações.
 * @param int $usuario_origem_id ID do usuário que envia
 * @param int $usuario_destino_id ID do usuário que recebe
 * @param int $conta_origem_id ID da conta de origem do remetente
 * @param int $conta_destino_id ID da conta de destino do destinatário
 * @param float $valor Valor da transferência
 * @param string $descricao Descrição da transferência
 * @param string $data_movimentacao Data da movimentação (YYYY-MM-DD)
 * @return bool Sucesso
 */
function criarTransferenciaComMovimentos($usuario_origem_id, $usuario_destino_id, $conta_origem_id, $conta_destino_id, $valor, $descricao, $data_movimentacao) {
    $transferencia = criarTransferencia($usuario_origem_id, $usuario_destino_id, $valor, $descricao);
    // Criar ambas movimentações como pendentes. A confirmação será feita pelo usuário destinatário.
    criarMovimentacao($usuario_origem_id, $conta_origem_id, 'despesa', 'Transferência para ' . buscarUsuarioPorId($usuario_destino_id)['nome'] . ': ' . $descricao, $valor, $data_movimentacao, 'pendente', $transferencia['id']);
    criarMovimentacao($usuario_destino_id, $conta_destino_id, 'receita', 'Transferência de ' . buscarUsuarioPorId($usuario_origem_id)['nome'] . ': ' . $descricao, $valor, $data_movimentacao, 'pendente', $transferencia['id']);
    return true;
}

/**
 * Cria uma transferência entre contas do mesmo usuário
 * @param int $usuario_id ID do usuário
 * @param int $conta_origem_id ID da conta de origem
 * @param int $conta_destino_id ID da conta de destino
 * @param float $valor Valor da transferência
 * @param string $descricao Descrição da transferência
 * @param string|null $data_movimentacao Data da transferência
 * @return bool Sucesso
 */
function criarTransferenciaConta($usuario_id, $conta_origem_id, $conta_destino_id, $valor, $descricao, $data_movimentacao = null) {
    $data_movimentacao = $data_movimentacao ?: date('Y-m-d');

    $transferencia_id = uniqid('tc_', true);
    criarMovimentacao($usuario_id, $conta_origem_id, 'despesa', 'Transferência para conta #' . $conta_destino_id . ': ' . $descricao, $valor, $data_movimentacao, 'confirmado', $transferencia_id);
    criarMovimentacao($usuario_id, $conta_destino_id, 'receita', 'Transferência de conta #' . $conta_origem_id . ': ' . $descricao, $valor, $data_movimentacao, 'confirmado', $transferencia_id);

    return true;
}

/**
 * Busca extratos visíveis para um usuário
 * @param int $usuario_id ID do usuário
 * @return array Array com extratos visíveis
 */
function buscarExtratosVisiveis($usuario_id) {
    $usuarios = lerCSV(USERS_FILE);
    $resultado = [];
    
    foreach ($usuarios as $usuario) {
        $resultado[] = [
            'usuario_id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'acesso_total' => $usuario['id'] == $usuario_id
        ];
    }
    
    return $resultado;
}

?>