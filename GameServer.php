<?php

class GameServer{
    private $address;
    private $port;
    private $server;
    private $clients = [];
    private $nicknames = [];
    private $scores = [0, 0];
    private $questions = [];

    public function __construct($address = '127.0.0.1', $port = 10005){
        $this->address = $address;
        $this->port = $port;
        $this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_bind($this->server, $this->address, $this->port);
        socket_listen($this->server);
        echo "Servidor iniciado em {$this->address}:{$this->port}\n";
        $this->loadQuestions();
    }

    private function loadQuestions(){
        $path = __DIR__ . "/questions.json";
        $json = @file_get_contents($path);
        $data = json_decode($json, true);

        if (!is_array($data) || empty($data)) {
            echo "Erro: $path nao encontrado ou invalido.\n";
            exit(1);
        }

        $this->questions = $data;
        shuffle($this->questions);
    }

    private function send($sock, $msg){
        socket_write($sock, $msg . "\n");
        usleep(100000);
    }

    private function recv($sock){
        return trim(socket_read($sock, 2048, PHP_NORMAL_READ));
    }

    private function checkAnswer($given, $correct){
        return strtoupper(trim($given)) === $correct;
    }

    private function processResponse(&$score, $correct, $pointsRight, $pointsWrong){
        if ($correct) {
            $score += $pointsRight;
            return "ACERTOU (+$pointsRight)";
        } else {
            $score -= $pointsWrong;
            return "ERROU (-$pointsWrong)";
        }
    }

    private function saveScoreboard(){
        $file = fopen("scoreboard.txt", "a");
        $time = date("Y-m-d H:i:s");
        fwrite($file, "$time - {$this->nicknames[0]}: {$this->scores[0]} | {$this->nicknames[1]}: {$this->scores[1]}\n");
        fclose($file);
    }

    public function run(){
        while (count($this->clients) < 2) {
            echo "Aguardando cliente...\n";
            $client = socket_accept($this->server);
            echo "Cliente conectado\n";
            $this->send($client, "Digite seu nome:");
            $nickname = $this->recv($client);
            $this->clients[] = $client;
            $this->nicknames[] = $nickname;
            echo "$nickname conectado.\n";
            $this->send($client, "Bem-vindo ao Passa ou Repassa, $nickname!");
        }

        $this->send($this->clients[0], "Aguardando o outro jogador...");
        sleep(1);
        $this->send($this->clients[1], "Iniciando o jogo!");

        $current = rand(0, 1);
        $other = 1 - $current;

        while (!empty($this->questions)) {
            $q = array_shift($this->questions);
            $qtext = $q['question'] . "\n" . implode("\n", $q['options']);
            foreach ($this->clients as $client) $this->send($client, "\nPergunta: $qtext");

            $this->send($this->clients[$current], "Sua vez. Digite: RESPONDER - X ou PASSA");
            $res = $this->recv($this->clients[$current]);

            if (str_starts_with(strtoupper($res), "RESPONDER")) {
                $ans = trim(explode("-", $res)[1] ?? '');
                $msg = $this->processResponse($this->scores[$current], $this->checkAnswer($ans, $q['answer']), 5, 5);
                $this->send($this->clients[$current], $msg);
            } elseif (strtoupper($res) == "PASSA") {
                $this->send($this->clients[$other], "RESPOSTA OU REPASSA?");
                $res2 = $this->recv($this->clients[$other]);

                if (str_starts_with(strtoupper($res2), "RESPONDER")) {
                    $ans = trim(explode("-", $res2)[1] ?? '');
                    $msg = $this->processResponse($this->scores[$other], $this->checkAnswer($ans, $q['answer']), 7, 5);
                    $this->send($this->clients[$other], $msg);
                } elseif (strtoupper($res2) == "REPASSA") {
                    $this->send($this->clients[$current], "ADVERSARIO REPASSOU - VOCE DEVE RESPONDER: RESPONDER - X");
                    $res3 = $this->recv($this->clients[$current]);
                    if (str_starts_with(strtoupper($res3), "RESPONDER")) {
                        $ans = trim(explode("-", $res3)[1] ?? '');
                        $msg = $this->processResponse($this->scores[$current], $this->checkAnswer($ans, $q['answer']), 10, 3);
                        $this->send($this->clients[$current], $msg);
                    } else {
                        $this->send($this->clients[$current], "Comando invalido. Pergunta perdida.");
                        continue;
                    }
                } else {
                    $this->send($this->clients[$other], "Comando invalido. Pergunta perdida.");
                    continue;
                }
            } else {
                $this->send($this->clients[$current], "Comando invalido. Pergunta perdida.");
                continue;
            }

            $scoreStr = "Placar: {$this->nicknames[0]} = {$this->scores[0]} | {$this->nicknames[1]} = {$this->scores[1]}";
            foreach ($this->clients as $client) $this->send($client, $scoreStr);
            $this->saveScoreboard();

            if ($this->scores[$current] >= 30 || $this->scores[$other] >= 30) {
                $winner = $this->scores[$current] >= 30 ? $current : $other;
                foreach ($this->clients as $client) $this->send($client, "FIM DE JOGO! Vencedor: {$this->nicknames[$winner]}");
                break;
            }
            [$current, $other] = [$other, $current];
        }

        foreach ($this->clients as $c) socket_close($c);
        socket_close($this->server);
    }
}

$game = new GameServer();
$game->run();
