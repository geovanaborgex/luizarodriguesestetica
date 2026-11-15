
<?php
require_once __DIR__ . '/pagamento-service.php';

if (isset($_GET['teste']) && $_GET['teste'] === '1') {
    $pedido = [
        'linha' => 2,
        'dados' => [
            'TESTE123',
            date('c'),
            'Cliente Teste',
            '37999999999',
            '["Design Simples", "Manutenção"]',
            '2026-11-20',
            '15:30',
            50000,
            10000,
            'CONFIRMADO',
            '',
            '',
            '',
            ''
        ]
    ];

    $pedido = $pedido['dados'];

    // Continue aqui com a montagem da página de confirmação.
}

date_default_timezone_set('America/Sao_Paulo');

function escapar(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function exibirRetorno(
    string $titulo,
    string $mensagem,
    string $tipo = 'erro',
    string $botao = '',
    string $link = ''
): void {
    $cor = $tipo === 'sucesso' ? '#6B6E55' : '#C9A86A';

    echo '<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . escapar($titulo) . '</title>
        <style>
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                background: #f7f6f2;
                font-family: Arial, sans-serif;
                color: #444;
            }
            .card {
                background: white;
                max-width: 440px;
                width: 100%;
                padding: 35px 25px;
                border-radius: 16px;
                text-align: center;
                box-shadow: 0 5px 25px rgba(0,0,0,.07);
            }
            .icone {
                font-size: 52px;
                margin-bottom: 15px;
            }
            h1 {
                color: ' . $cor . ';
                font-size: 25px;
            }
            p {
                line-height: 1.6;
                color: #666;
            }
            .botao {
                display: block;
                margin-top: 25px;
                padding: 16px;
                background: #25D366;
                color: white;
                text-decoration: none;
                border-radius: 9px;
                font-weight: bold;
            }
            .detalhes {
                background: #f7f6f2;
                padding: 15px;
                border-radius: 10px;
                text-align: left;
                margin-top: 20px;
                line-height: 1.8;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icone">' .
                ($tipo === 'sucesso' ? '✓' : 'ℹ') .
            '</div>
            <h1>' . escapar($titulo) . '</h1>
            <p>' . escapar($mensagem) . '</p>';

    if ($link !== '') {
        echo '<a class="botao" href="' . escapar($link) .
             '" target="_blank" rel="noopener noreferrer">' .
             escapar($botao) .
             '</a>';
    }

    echo '</div></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    exibirRetorno(
        'Acesso inválido',
        'Não foi possível processar esta solicitação.'
    );
}

$pedidoId = trim((string)($_GET['order_nsu'] ?? ''));
$slug = trim((string)($_GET['slug'] ?? ''));
$transacao = trim((string)($_GET['transaction_nsu'] ?? ''));
$comprovante = trim((string)($_GET['receipt_url'] ?? ''));

try {
    $resultado = pagamentoConfirmar(
        $pedidoId,
        $slug,
        $transacao,
        $comprovante
    );

    if (!($resultado['sucesso'] ?? false)) {
        exibirRetorno(
            'Pagamento em processamento',
            $resultado['mensagem'] ??
            'Não foi possível confirmar o pagamento. Entre em contato com o salão antes de tentar pagar novamente.'
        );
    }

    // Recupera os dados do pedido já salvo na planilha.
    $registro = pagamentoBuscarPedido($pedidoId);
    $pedido = $registro['dados'];

    // Confere novamente o status registrado.
    if (strtoupper(trim((string)$pedido[9])) !== 'CONFIRMADO') {
        exibirRetorno(
            'Pagamento em processamento',
            'Seu pagamento ainda está sendo confirmado. Entre em contato com o salão antes de tentar pagar novamente.'
        );
    }

    $nome = trim((string)$pedido[2]);
    $telefone = trim((string)$pedido[3]);

    $procedimentos = json_decode(
        (string)$pedido[4],
        true
    );

    if (!is_array($procedimentos)) {
        $procedimentos = [];
    }

    $data = trim((string)$pedido[5]);
    $horario = trim((string)$pedido[6]);

    $dataFormatada = date(
        'd/m/Y',
        strtotime($data)
    );

    $valorTotal = 'R$ ' . number_format(
            ((int)$pedido[7]) / 100,
            2,
            ',',
            '.'
        );

    $valorSinal = 'R$ ' . number_format(
            ((int)$pedido[8]) / 100,
            2,
            ',',
            '.'
        );

    $listaProcedimentos = implode(', ', $procedimentos);

    $mensagem = "Olá, Luiza! Acabei de realizar o pagamento do sinal do meu agendamento pelo site.\n\n"
        . "Cliente: {$nome}\n"
        . "Telefone: {$telefone}\n"
        . "Procedimento: {$listaProcedimentos}\n"
        . "Data: {$dataFormatada}\n"
        . "Horário: {$horario}\n"
        . "Valor total dos procedimentos: {$valorTotal}\n"
        . "Sinal pago antecipadamente (20%): {$valorSinal}\n\n"
        . "Gostaria de confirmar que está tudo certo. Obrigada!";

    $numeroLuiza = '5537999049543';

    $linkWhatsApp = 'https://wa.me/' .
        $numeroLuiza .
        '?text=' .
        rawurlencode($mensagem);

    echo '<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Agendamento confirmado</title>
        <style>
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                background: #f7f6f2;
                font-family: Arial, sans-serif;
                color: #444;
            }
            .card {
                background: white;
                max-width: 460px;
                width: 100%;
                padding: 35px 25px;
                border-radius: 16px;
                text-align: center;
                box-shadow: 0 5px 25px rgba(0,0,0,.07);
            }
            .icone {
                width: 65px;
                height: 65px;
                border-radius: 50%;
                background: #e8eee3;
                color: #6B6E55;
                font-size: 40px;
                line-height: 65px;
                margin: 0 auto 15px;
            }
            h1 {
                color: #6B6E55;
                font-size: 25px;
            }
            p {
                color: #666;
                line-height: 1.6;
            }
            .detalhes {
                background: #f7f6f2;
                padding: 16px;
                border-radius: 10px;
                text-align: left;
                margin-top: 22px;
                line-height: 1.8;
                font-size: 14px;
            }
            .botao {
                display: block;
                margin-top: 25px;
                padding: 16px;
                background: #25D366;
                color: white;
                text-decoration: none;
                border-radius: 9px;
                font-weight: bold;
            }
            .observacao {
                font-size: 12px;
                margin-top: 15px;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icone">✓</div>
            <h1>Pagamento confirmado!</h1>
            <p>Seu pagamento foi aprovado e seu agendamento está confirmado. Estamos esperando por você!</p>

            <div class="detalhes">
                <strong>Confira seu agendamento:</strong><br>
                Cliente: ' . escapar($nome) . '<br>
                Procedimento: ' . escapar($listaProcedimentos) . '<br>
                Data: ' . escapar($dataFormatada) . '<br>
                Horário: ' . escapar($horario) . '<br>
                Valor total: ' . escapar($valorTotal) . '<br>
                Sinal pago antecipadamente (20%): <strong>' . escapar($valorSinal) . '</strong>
            </div>

            <p>Agora, se desejar, envie uma mensagem para a Luiza pelo WhatsApp informando que realizou o pagamento.</p>

            <a class="botao" href="' . escapar($linkWhatsApp) . '" target="_blank" rel="noopener noreferrer">
                Enviar confirmação para Luiza pelo WhatsApp
            </a>

            <p class="observacao">Basta clicar no botão e enviar a mensagem pelo WhatsApp.</p>
        </div>
    </body>
    </html>';

} catch (Throwable $e) {
    error_log(
        'Erro no retorno InfinitePay: ' . $e->getMessage()
    );

    exibirRetorno(
        'Não foi possível confirmar agora',
        'Não tente realizar outro pagamento. Entre em contato com o salão para verificar a situação do seu agendamento.'
    );
}
