<?php
header("Content-Type: application/json");

$apiToken = 'mbnsQwXer5d58rzrSMKv7S87HoVFbne8aDNEQUPSGQzfb43LeUa6oRBLqBpj';
$apiUrl = 'https://api.goatpayments.com.br/api/public/v1/transactions';

// Função para gerar CPF válido
function gerarCPF() {
    $n = [];
    for ($i = 0; $i < 9; $i++) {
        $n[$i] = rand(0, 9);
    }

    // Calcula o primeiro dígito verificador
    $d1 = 0;
    for ($i = 0, $j = 10; $i < 9; $i++, $j--) {
        $d1 += $n[$i] * $j;
    }
    $d1 = 11 - ($d1 % 11);
    $d1 = ($d1 >= 10) ? 0 : $d1;

    // Calcula o segundo dígito verificador
    $d2 = 0;
    for ($i = 0, $j = 11; $i < 9; $i++, $j--) {
        $d2 += $n[$i] * $j;
    }
    $d2 += $d1 * 2;
    $d2 = 11 - ($d2 % 11);
    $d2 = ($d2 >= 10) ? 0 : $d2;

    return implode('', $n) . $d1 . $d2;
}

// Função para gerar nome aleatório
function gerarNomeAleatorio() {
    $nomes = ["Ana", "Bruno", "Carla", "Daniel", "Eduarda", "Fernando", "Gabriela", "Hugo", "Isabela", "Joao"];
    $sobrenomes = ["Silva", "Santos", "Oliveira", "Souza", "Lima", "Costa", "Pereira", "Almeida", "Nascimento", "Ferreira"];
    return $nomes[array_rand($nomes)] . " " . $sobrenomes[array_rand($sobrenomes)];
}

// Função para gerar telefone aleatório (formato brasileiro)
function gerarTelefoneAleatorio() {
    $ddd = str_pad(rand(11, 99), 2, '0', STR_PAD_LEFT);
    $primeirosCinco = str_pad(rand(90000, 99999), 5, '0', STR_PAD_LEFT);
    $ultimosQuatro = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    return "(" . $ddd . ") " . $primeirosCinco . "-" . $ultimosQuatro;
}

// Função para gerar endereço aleatório
function gerarEnderecoAleatorio() {
    $ruas = ["Rua Principal", "Avenida Central", "Travessa da Paz", "Alameda das Flores", "Estrada Velha"];
    $numeros = rand(100, 1000);
    $complementos = ["Apto 101", "Casa B", "Bloco C", "Fundos", "" ];
    $bairros = ["Centro", "Jardim America", "Vila Nova", "Santa Cruz", "Liberdade"];
    $cidades = ["São Paulo", "Rio de Janeiro", "Belo Horizonte", "Curitiba", "Porto Alegre"];
    $estados = ["SP", "RJ", "MG", "PR", "RS"];
    $cep = str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);

    return [
        "street_name" => $ruas[array_rand($ruas)],
        "number" => (string)$numeros,
        "complement" => $complementos[array_rand($complementos)],
        "neighborhood" => $bairros[array_rand($bairros)],
        "city" => $cidades[array_rand($cidades)],
        "state" => $estados[array_rand($estados)],
        "zip_code" => $cep
    ];
}

$nomeAleatorio = gerarNomeAleatorio();
$telefoneAleatorio = gerarTelefoneAleatorio();
$cpfAleatorio = gerarCPF();
$enderecoAleatorio = gerarEnderecoAleatorio();

$amountInCents = isset($_GET['amount']) ? intval($_GET['amount']) : 7000;
$productTitle = "Doação - (Obrigatório)";
if ($amountInCents > 0) {
    $productTitle = "Doação de " . number_format($amountInCents/100, 2, ',', '.') . " - (Obrigatório)";
}

$data = [
    "amount" => $amountInCents,
    "offer_hash" => "95fcotaqbj", // Mantenha ou substitua por um hash de oferta válido
    "payment_method" => "pix",
    "customer" => [
        "name" => $nomeAleatorio,
        "email" => "cliente+" . time() . "@example.com",
        "phone_number" => preg_replace('/\D/', '', $telefoneAleatorio),
        "document" => $cpfAleatorio,
        "street_name" => $enderecoAleatorio["street_name"],
        "number" => $enderecoAleatorio["number"],
        "complement" => $enderecoAleatorio["complement"],
        "neighborhood" => $enderecoAleatorio["neighborhood"],
        "city" => $enderecoAleatorio["city"],
        "state" => $enderecoAleatorio["state"],
        "zip_code" => $enderecoAleatorio["zip_code"]
    ],
    "cart" => [
        [
            "product_hash" => "2srgtebcxd", // Mantenha ou substitua por um hash de produto válido
            "title" => $productTitle,
            "price" => $amountInCents,
            "quantity" => 1,
            "operation_type" => 1,
            "tangible" => true
        ]
    ],
    "tracking" => [
        "utm_source" => $_GET['utm_source'] ?? '',
        "utm_medium" => $_GET['utm_medium'] ?? '',
        "utm_campaign" => $_GET['utm_campaign'] ?? '',
        "utm_term" => $_GET['utm_term'] ?? '',
        "utm_content" => $_GET['utm_content'] ?? ''
    ],
    "installments" => 1,
    "expire_in_days" => 1,
    "postback_url" => "https://seusite.com/notificacao"
];

// Envia a requisição
$ch = curl_init($apiUrl . '?api_token=' . $apiToken);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo $response;
} else {
    echo json_encode([
        'error' => 'Falha ao se comunicar com a API de pagamento.',
        'details' => json_decode($response)
    ]);
}
?>