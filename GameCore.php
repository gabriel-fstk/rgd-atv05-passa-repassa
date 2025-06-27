<?php

class GameCore {
    public $players = []; // [resourceId => ['nickname' => ..., 'score' => ...]]
    public $order = [];   // resourceIds na ordem de jogo
    public $questions = [];
    public $currentQuestion = null;
    public $currentPlayer = null;
    public $waitingFor = null; // Quem deve responder agora
    public $roundStarter = null;
    public $passCount = 0;

    public function __construct($questions) {
        if (!is_array($questions) || empty($questions)) {
            throw new Exception("Perguntas inválidas ou não encontradas!");
        }

        $this->questions = $questions;
        shuffle($this->questions);
    }

    public function addPlayer($resourceId, $nickname) {
        $this->players[$resourceId] = ['nickname' => $nickname, 'score' => 0];
        $this->order[] = $resourceId;
    }

    public function getNextQuestion() {
        if (empty($this->questions)) return null;
        $this->currentQuestion = array_shift($this->questions);
        $this->passCount = 0;
        return $this->currentQuestion;
    }

    public function checkAnswer($given) {
        if (!$this->currentQuestion) return false;
        $correct = $this->currentQuestion['answer'];
        return strtoupper(trim($given)) === strtoupper($correct);
    }

    public function getOpponent($resourceId) {
        foreach ($this->order as $id) {
            if ($id !== $resourceId) return $id;
        }
        return null;
    }

    public function resetScores() {
        foreach ($this->players as &$p) $p['score'] = 0;
    }
}