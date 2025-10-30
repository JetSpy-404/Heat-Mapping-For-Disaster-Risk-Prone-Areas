<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class ChatServer implements MessageComponentInterface {
    protected $clients;
    protected $users;
    protected $userConnections;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
        $this->userConnections = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! (" . spl_object_hash($conn) . ")\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        if ($data['type'] === 'register') {
            $user_id = $data['user_id'];
            $this->users[spl_object_hash($from)] = $user_id;
            // Track multiple connections per user
            if (!isset($this->userConnections[$user_id])) {
                $this->userConnections[$user_id] = [];
            }
            $this->userConnections[$user_id][] = $from;
            echo "User {$user_id} registered\n";
        } elseif ($data['type'] === 'message') {
            $this->handleMessage($from, $data);
        }
    }

    protected function handleMessage(ConnectionInterface $from, $data) {
        $sender_id = $this->users[spl_object_hash($from)] ?? null;
        $receiver_id = $data['receiver_id'];
        $message = $data['message'];

        if (!$sender_id) {
            return;
        }

        // Send to all connections of the receiver if online for real-time delivery
        if (isset($this->userConnections[$receiver_id])) {
            foreach ($this->userConnections[$receiver_id] as $client) {
                if ($client !== $from) { // Don't send to sender's own connections
                    $client->send(json_encode([
                        'type' => 'message',
                        'sender_id' => $sender_id,
                        'message' => $message,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]));
                }
            }
        }

        // Send confirmation to sender
        $from->send(json_encode([
            'type' => 'sent',
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]));

        // Save message to database asynchronously to avoid blocking
        $this->saveMessageAsync($sender_id, $receiver_id, $message);
    }

    protected function saveMessageAsync($sender_id, $receiver_id, $message) {
        try {
            require_once 'db.php';
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, timestamp) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$sender_id, $receiver_id, $message]);
        } catch (Exception $e) {
            echo "Error saving message: " . $e->getMessage() . "\n";
        }
    }

    public function onClose(ConnectionInterface $conn) {
        unset($this->users[spl_object_hash($conn)]);
        $this->clients->detach($conn);
        echo "Connection " . spl_object_hash($conn) . " has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    8080
);

echo "WebSocket server started on port 8080\n";
$server->run();
