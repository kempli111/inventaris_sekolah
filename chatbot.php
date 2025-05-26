<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>SIASET Chatbot</title>
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* (CSS sama seperti versi sebelumnya) */
    /* ... */
  </style>
</head>
<body>
  <div class="chat-container">
    <div class="chat-header">
      <i class="fas fa-robot"></i> SIASET Chatbot
    </div>
    <div class="chat-messages" id="chatMessages">
      <div class="message bot-message">
        Halo! Saya adalah asisten SIASET yang siap membantu Anda...
      </div>
    </div>
    <div class="typing-indicator" id="typingIndicator">
      <span>•</span><span>•</span><span>•</span>
    </div>
    <div class="chat-input">
      <form id="chatForm" onsubmit="return sendMessage(event)">
        <div class="input-group">
          <input type="text" id="messageInput" placeholder="Ketik pesan Anda..."
                 autocomplete="off" required>
          <button type="submit" id="sendButton">
            <i class="fas fa-paper-plane"></i>
          </button>
        </div>
      </form>
      <div class="suggestions">
        <!-- suggestion chips -->
      </div>
    </div>
  </div>

  <script>
    const chatMessages    = document.getElementById('chatMessages');
    const messageInput    = document.getElementById('messageInput');
    const typingIndicator = document.getElementById('typingIndicator');
    const sendButton      = document.getElementById('sendButton');

    function appendMessage(text, isUser = false) {
      const div = document.createElement('div');
      div.className = 'message ' + (isUser ? 'user-message' : 'bot-message');
      div.textContent = text;
      chatMessages.appendChild(div);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function showTyping() { typingIndicator.style.display = 'block'; }
    function hideTyping() { typingIndicator.style.display = 'none'; }

    async function sendMessage(e) {
      e.preventDefault();
      const text = messageInput.value.trim();
      if (!text) return;

      appendMessage(text, true);
      messageInput.value = '';
      messageInput.disabled = true;
      sendButton.disabled = true;
      showTyping();

      try {
        const res = await fetch('api_endpoint.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ message: text })
        });

        const data = await res.json();
        console.log('Response:', {
          status: res.status,
          statusText: res.statusText,
          data: data
        });

        hideTyping();
        
        if (res.ok && data.success) {
          appendMessage(data.response);
        } else {
          const errorMsg = data.message || 'Terjadi kesalahan pada server';
          appendMessage('Error: ' + errorMsg);
          console.error('Server error:', data);
        }

      } catch (err) {
        console.error('Network error:', err);
        hideTyping();
        appendMessage('Terjadi kesalahan koneksi. Silakan coba lagi.');
      } finally {
        messageInput.disabled = false;
        sendButton.disabled = false;
        messageInput.focus();
      }
    }

    // Kirim saat tekan Enter tanpa Shift
    messageInput.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendButton.click();
      }
    });
  </script>
</body>
</html>
