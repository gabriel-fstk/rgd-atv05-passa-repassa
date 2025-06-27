const chatBox = document.getElementById('chat-box');
const msgInput = document.getElementById('msg-input');
const sendBtn = document.getElementById('send-btn');

function appendMessage(msg, type) {
  const div = document.createElement('div');
  div.className = `message ${type}`;
  div.textContent = msg;
  chatBox.appendChild(div);
  chatBox.scrollTop = chatBox.scrollHeight;
}

// --- WEBSOCKET ---
let ws;
function connectWs() {
  ws = new WebSocket('ws://localhost:8081');
  ws.onopen = () => appendMessage('Conectado ao servidor WebSocket!', 'server');
  ws.onmessage = (event) => appendMessage(event.data, 'server');
  ws.onerror = (err) => appendMessage('Erro WebSocket', 'server');
  ws.onclose = () => appendMessage('Desconectado do servidor WebSocket.', 'server');
}

function sendMessage() {
  const text = msgInput.value.trim();
  if (!text) return;
  appendMessage(text, 'user');
  msgInput.value = '';
  if (ws && ws.readyState === WebSocket.OPEN) {
    ws.send(text);
  } else {
    appendMessage('WebSocket não conectado.', 'server');
  }
}

sendBtn.onclick = sendMessage;
msgInput.addEventListener('keypress', e => {
  if (e.key === 'Enter') sendMessage();
});

window.onload = () => {
  connectWs();
};