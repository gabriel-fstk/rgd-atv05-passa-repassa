# Passa ou Repassa - Jogo em Rede com PHP e Sockets 🎮💬

Um jogo de perguntas e respostas baseado no famoso "Passa ou Repassa"!
Desenvolvido com **PHP (sockets TCP e WebSocket)**, interface **web tipo chat** (HTML/JS) e suporte a **multi-jogador em rede**.

---

## 📁 Estrutura de Arquivos

```
/passarepassa/
├── GameServer.php        # 🎮 Servidor TCP que controla o jogo
├── GameClient.php        # 💻 Cliente terminal para testes
├── GameWsServer.php      # 🌐 Servidor WebSocket - Ratchet
├── GameCore.php          # 🧠 Lógica central do jogo (servidor Web)
├── questions.json        # ❓ Perguntas do jogo (editável)
├── scoreboard.txt        # 🧾 Registro de pontuação histórica
├── index.html            # 🖥️ Interface gráfica (modo chat)
├── script.js             # ⚙️ Lógica JS da interface
├── composer.json         # 📦 Dependências do projeto
├── vendor/               # 📁 Pacotes do Composer (inclui Ratchet)
└── README.md             # 📘 Documentação
```

---

## 🎯 Funcionalidades

- 👥 Suporte a 2 jogadores via rede (socket TCP | WebSocket)
- 🕹️ Regras: Responder, Passar, Repassar
- 🧮 Pontuação dinâmica:
  - ✅ A resposta correta: +5 pontos
  - 🔁 Passada: +7 pontos
  - 🔄 Repassada: +10 pontos
- 🗃️ Histórico salvo em `scoreboard.txt` (para o Servidor TCP)
- 💬 Interface chat com botão de envio e histórico

---

## 🚀 Como executar

### 1. 📦 Requisitos

- PHP 7.4+
- 🌐 Navegador web (Chrome, Firefox...)
- ⚙️ Extensão `sockets` habilitada no PHP
- 🧰 Composer instalado

### 2. 🧰 Instalar Ratchet (WebSocket)
Este projeto usa Ratchet como servidor WebSocket.

a) Instalar o Composer (caso ainda não tenha)

```bash
# Linux/macOS
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Windows: baixe em https://getcomposer.org/download/
```

b) Instalar o Ratchet
No diretório /passarepassa, execute:

```bash
composer require cboden/ratchet
```

> Isso criará a pasta vendor/ e o arquivo composer.json.

⚠️ Certifique-se de que o GameWsServer.php tenha o autoload do Composer:

```PHP
require __DIR__ . '/vendor/autoload.php';
```

### 3. 🔌 Rodar servidor WebSocket do jogo

```bash
php GameWsServer.php
```

> Irá abrir WebSocket e aguardar 2 jogadores


### 4. 🧪 Roda servidor WebSocket interface client (chat)

- Use o **Live Server** no VS Code ou `php -S`
- Utilize dois terminais e inicie o client server em portas diferentes

```bash
php -S localhost:8080 | php -S localhost:8081
```

- Acesse `http://localhost:8080/` (player 1)
- Acesse `http://localhost:8081/` (player 2)

### 5. 🖥️ Alternativa: Jogador no terminal (server TCP)

```bash
php GameClient.php
```

- Você pode usar dois terminais, ou um terminal + navegador para jogar entre duas interfaces diferentes.

---

## ✍️ Perguntas personalizadas

Edite o arquivo `questions.json`:

```json
[
  {
    "question": "Qual a capital do Brasil?",
    "options": ["A) Rio", "B) Brasília", "C) SP"],
    "answer": "B"
  }
]
```
📌 Importante:

- Evite repetir letras nas alternativas
- A resposta deve ser apenas a **letra** (`"A"`, `"B"`, etc)

---

## 🏆 Pontuação histórica (servidor TCP)

Após cada rodada, a pontuação dos jogadores é registrada em:

```
scoreboard.txt
```

Inclui data/hora e placar de ambos os jogadores.

---

## 👨‍💻 Autores

📚 Projeto acadêmico IFRS - Redes de Computadores 
👨‍🏫 Prof. Luciano V. Gonçalves

🧑‍💻 Implementação por: **[Gabriel Soares](https://github.com/gabriel-fstk)**

---

## 🌟 Sugestões futuras

- 🧩 Suporte a múltiplos jogadores (3+)
- 🧪 Jogo 100% em WebSocket (sem TCP)
- 🗄️ Banco de dados para registrar o histórico
- 📊 Dashboard com vitórias, rankings e estatísticas

---

🎉 Bom jogo e divirta-se! 🥳
