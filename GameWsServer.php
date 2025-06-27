<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'vendor/autoload.php';
require_once 'GameCore.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class GameWsServer implements MessageComponentInterface {
    protected $clients = [];
    protected $core = null;
    protected $questions;
    protected $score = 5;

    public function __construct() {
        $this->questions = json_decode(file_get_contents(__DIR__ . "/questions.json"), true);
    }

    public function onOpen(ConnectionInterface $conn) {
        try {
            $this->clients[$conn->resourceId] = $conn;
            if (!$this->core) {
                $this->core = new GameCore($this->questions);
            }
            $conn->send("Digite seu nome:");
        } catch (Exception $e) {
            $conn->send("Erro no servidor: " . $e->getMessage());
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            if (!$this->core) {
                $from->send("O jogo acabou! Recarregue a pagina para iniciar uma nova partida.");
                return;
            }

            $rid = $from->resourceId;
            $core = $this->core;

            // Cadastro de nickname
            if (!isset($core->players[$rid])) {
                $core->addPlayer($rid, $msg);
                $from->send("Bem-vindo, $msg!");
                if (count($core->players) == 2) {
                    // Dois jogadores conectados, iniciar partida
                    $this->startGame();
                } else {
                    $from->send("Aguardando outro jogador...");
                }
                return;
            }

            if (count($core->players) < 2) {
                $from->send("Aguardando outro jogador...");
                return;
            }

            if ($core->waitingFor !== $rid) {
                $from->send("Aguarde sua vez.");
                return;
            }

            $msg = strtoupper(trim($msg));
            if ($msg !== "") {
                $opcao = strtoupper(preg_replace('/[^A-Z]/', '', $msg));
                $acertou = $core->checkAnswer($opcao);

                // PASSA
                if ($msg === "PASSA") {
                    $this->score = 7;
                    $oponente = $core->getOpponent($rid);
                    $core->waitingFor = $oponente;
                    $this->clients[$oponente]->send("RESPONDA ou REPASSA");
                    return;
                }

                // REPASSA
                if ($msg === "REPASSA") {
                    $core->passCount++;
                    if ($core->passCount >= 2) {
                        $this->score = 10;
                        // Volta para quem iniciou a rodada, agora é obrigatorio responder
                        $core->waitingFor = $core->roundStarter;
                        $this->clients[$core->roundStarter]->send("ADVERSARIO REPASSOU PERGUNTA ? VOCE DEVE RESPONDER");
                    } else {
                        // Volta para o outro jogador
                        $oponente = $core->getOpponent($rid);
                        $core->waitingFor = $oponente;
                        $this->clients[$oponente]->send("RESPONDA ou REPASSA");
                    }
                    return;
                }

                if ($acertou) {
                    $core->players[$rid]['score'] += $this->score;
                    $this->broadcast("{$core->players[$rid]['nickname']} ACERTOU! (+".$this->score." pontos)");
                } else {
                    $core->players[$rid]['score'] -= 5;
                    $this->broadcast("{$core->players[$rid]['nickname']} ERROU! (-5 pontos)");
                }
                $this->broadcast($this->placar());
                if ($core->players[$rid]['score'] >= 30) {
                    $this->broadcast("FIM DE JOGO! Vencedor: {$core->players[$rid]['nickname']}");
                    $this->resetGame();
                    return;
                }
                $this->nextRound();
                return;
            }

            $from->send("Comando invalido. Use OPCAO X, PASSA ou REPASSA.");

        } catch (\Throwable $e) {
            $from->send("Erro interno: " . $e->getMessage());
            echo "Erro interno: " . $e->getMessage() . "\n";
        }
    }

    private function startGame() {
        $core = $this->core;
        $core->resetScores();
        $core->order = array_keys($core->players);
        shuffle($core->order);
        $core->roundStarter = $core->order[0];
        $core->waitingFor = $core->roundStarter;
        $q = $core->getNextQuestion();
        $this->broadcast("Nova partida iniciada!");
        $this->broadcast($this->placar());
        $this->broadcastPergunta($q);
        $this->clients[$core->waitingFor]->send("Sua vez! OPCAO X ou PASSA");
    }

    private function nextRound() {
        $core = $this->core;
        // Alterna quem comeca
        $core->order = array_reverse($core->order);
        $core->roundStarter = $core->order[0];
        $core->waitingFor = $core->roundStarter;
        $q = $core->getNextQuestion();
        if (!$q) {
            $this->broadcast("Fim das perguntas! Empate.");
            $this->resetGame();
            return;
        }
        $this->broadcastPergunta($q);
        $this->clients[$core->waitingFor]->send("Sua vez! OPCAO X ou PASSA");
    }

    private function broadcastPergunta($q) {
        if (!$q || !isset($q['question']) || !isset($q['options'])) {
            $this->broadcast("Erro: Pergunta invalida ou nao encontrada.");
            return;
        }
        $msg = "Pergunta: {$q['question']}\n";
        foreach ($q['options'] as $opt) $msg .= "$opt\n";
        // $msg .= "Sua resposta (A, B, C...):";
        $this->broadcast($msg);
    }

    private function placar() {
        $core = $this->core;
        $p = $core->players;

        if (count($core->order) < 2) return "Aguardando jogadores...";

        return "Placar: {$p[$core->order[0]]['nickname']} ({$p[$core->order[0]]['score']}) x {$p[$core->order[1]]['nickname']} ({$p[$core->order[1]]['score']})";
    }

    private function broadcast($msg) {
        foreach ($this->clients as $rid => $c) {
        try {
            $c->send($msg);
        } catch (\Exception $e) {
            // Remove cliente problematico
            unset($this->clients[$rid]);
            if ($this->core && isset($this->core->players[$rid])) {
                unset($this->core->players[$rid]);
            }
        }
        }
    }

    private function resetGame() {
        $this->core = null;
        
        foreach ($this->clients as $c) {
            $c->send("O jogo acabou! Recarregue a pagina para jogar novamente.");
        }
    }

    public function onClose(ConnectionInterface $conn) {
        unset($this->clients[$conn->resourceId]);
        if ($this->core && isset($this->core->players[$conn->resourceId])) {
            unset($this->core->players[$conn->resourceId]);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new GameWsServer()
        )
    ),
    8081
);

$server->run();