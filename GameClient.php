<?php
class GameClient {
    private $socket;
    private $address;
    private $port;

    public function __construct($address = '127.0.0.1', $port = 10005) {
        $this->address = $address;
        $this->port = $port;
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!@socket_connect($this->socket, $this->address, $this->port)) {
            die("Erro ao conectar ao servidor\n");
        }
         echo "Conectado ao servidor!\n";
    }

    public function run() {
        while (true) {
            $msg = socket_read($this->socket, 2048, PHP_NORMAL_READ);
            if ($msg === false) {
                echo "Erro ao ler do servidor\n";
                break;
            }
            echo $msg;
            

            if (str_starts_with(trim($msg), 'Digite') || str_contains($msg, 'vez') || str_contains($msg, 'VOCE DEVE') || str_contains($msg, 'RESPOSTA OU REPASSA')) {
                echo "> ";
                $input = trim(fgets(STDIN));
                socket_write($this->socket, $input . "\n");
            }
        }
    }
}

$client = new GameClient();
$client->run();
