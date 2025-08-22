import Pusher from "pusher-js";

// Example Pusher setup
const pusher = new Pusher("your-app-key", {
  cluster: "mt1", // <--- You must provide a cluster
  wsHost: "localhost", // optional if using local WebSocket server
  wsPort: 6001,        // or your WebSocket port
  forceTLS: false,     // local dev usually false
  enabledTransports: ["ws", "wss"]
});

const channel = pusher.subscribe("issues");
channel.bind("IssueCreated", function (data) {
  console.log("New issue created:", data);
});
