<?php

$server = realpath(__DIR__ . '/../../../datapizza/integrations/mcp/server.php');
if ($server === false) {
    throw new RuntimeException('MCP server was not found');
}

$requests = [
    ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => [
        'protocolVersion' => '2025-11-25',
        'capabilities' => [],
        'clientInfo' => ['name' => 'offline-test', 'version' => '1.0']
    ]],
    ['jsonrpc' => '2.0', 'method' => 'notifications/initialized', 'params' => []],
    ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list', 'params' => []],
    ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'ping', 'params' => []],
    ['jsonrpc' => '2.0', 'id' => 4, 'method' => 'tools/call', 'params' => [
        'name' => 'calculator',
        'arguments' => ['expression' => '2+2']
    ]]
];

$input = implode("\n", array_map('json_encode', $requests)) . "\n";
$command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($server);
$pipes = [];
$process = proc_open($command, [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w']
], $pipes);

if (!is_resource($process)) {
    throw new RuntimeException('Could not start MCP server');
}

fwrite($pipes[0], $input);
fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exit = proc_close($process);

if ($exit !== 0 || trim($stderr) !== '') {
    throw new RuntimeException('MCP server failed: ' . trim($stderr));
}

$lines = array_values(array_filter(explode("\n", trim($stdout)), 'strlen'));
if (count($lines) !== 4) {
    throw new RuntimeException('MCP notification produced a response or framing was corrupted');
}

$responses = array_map(function ($line) {
    $decoded = json_decode($line, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('MCP output was not valid JSON');
    }
    return $decoded;
}, $lines);

if ($responses[0]['result']['protocolVersion'] !== '2025-11-25') {
    throw new RuntimeException('Unexpected MCP protocol version');
}

$tools = $responses[1]['result']['tools'];
$calculator = array_values(array_filter($tools, function ($tool) {
    return $tool['name'] === 'calculator';
}))[0] ?? null;
if ($calculator === null
    || $calculator['inputSchema']['required'] !== ['expression']) {
    throw new RuntimeException('Calculator MCP schema is incomplete');
}

$wikipedia = array_values(array_filter($tools, function ($tool) {
    return $tool['name'] === 'wikipedia_search';
}))[0] ?? null;
if ($wikipedia === null
    || $wikipedia['inputSchema']['required'] !== ['query']) {
    throw new RuntimeException('Legacy tool schema was not normalized');
}

if (($responses[3]['result']['content'][0]['text'] ?? null) !== 'Result: 4') {
    throw new RuntimeException('MCP calculator call failed');
}

echo "MCP STDIO framing, discovery, schema, ping, and tool call tests passed.\n";
