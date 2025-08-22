// src/services/websocket.js
import Echo from "laravel-echo";

window.Pusher = require("pusher-js"); // Needed even with Reverb

const echo = new Echo({
  broadcaster: "reverb",
  key: "local", // match your REVERB_APP_KEY in Laravel .env
  wsHost: "localhost", // where Reverb is running
  wsPort: 8080,        // default Reverb port
  forceTLS: false,
  disableStats: true,
});

export default echo;
