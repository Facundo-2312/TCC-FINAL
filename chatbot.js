(function () {
  var toggleButton = document.getElementById('chatbot-toggle');
  var panel = document.getElementById('chatbot-panel');
  var closeButton = document.getElementById('chatbot-close');
  var messages = document.getElementById('chatbot-messages');
  var form = document.getElementById('chatbot-form');
  var input = document.getElementById('chatbot-input');
  var quick = document.getElementById('chatbot-quick');

  if (!toggleButton || !panel || !messages || !form || !input || !quick) {
    return;
  }

  function addMessage(text, author) {
    var line = document.createElement('div');
    line.className = 'chatbot-msg chatbot-msg-' + author;
    line.textContent = text;
    messages.appendChild(line);
    messages.scrollTop = messages.scrollHeight;
  }

  function setSuggestions(items) {
    quick.innerHTML = '';
    if (!Array.isArray(items) || items.length === 0) {
      return;
    }

    items.slice(0, 5).forEach(function (item) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'chatbot-chip';
      btn.textContent = item;
      btn.addEventListener('click', function () {
        input.value = item;
        form.dispatchEvent(new Event('submit'));
      });
      quick.appendChild(btn);
    });
  }

  function setOpen(open) {
    if (open) {
      panel.classList.add('is-open');
      toggleButton.classList.add('is-hidden');
      input.focus();
      return;
    }

    panel.classList.remove('is-open');
    toggleButton.classList.remove('is-hidden');
  }

  async function askBot(question) {
    addMessage(question, 'user');
    input.value = '';

    try {
      var response = await fetch('/proj/chatbot_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: question })
      });

      var payload = await response.json();
      if (!response.ok || !payload || !payload.reply) {
        addMessage('No pude responder en este momento.', 'bot');
        return;
      }

      addMessage(payload.reply, 'bot');
      setSuggestions(payload.suggestions || []);
    } catch (error) {
      addMessage('Error de conexion con el asistente.', 'bot');
    }
  }

  toggleButton.addEventListener('click', function () {
    setOpen(true);
  });

  if (closeButton) {
    closeButton.addEventListener('click', function () {
      setOpen(false);
    });
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    var question = input.value.trim();
    if (!question) {
      return;
    }

    askBot(question);
  });

  addMessage('Hola, soy tu asistente. Preguntame por ubicacion, horarios o recaudado de hoy.', 'bot');
  setSuggestions([
    'Donde esta el local?',
    'Cual es el horario?',
    'Telefono de contacto',
    'Recaudado de hoy',
    'Propina recaudada'
  ]);
})();
