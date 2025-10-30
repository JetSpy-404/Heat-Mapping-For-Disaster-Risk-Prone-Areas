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

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! (" . spl_object_hash($conn) . ")\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        if ($data['type'] === 'register') {
            $this->users[spl_object_hash($from)] = $data['user_id'];
            echo "User {$data['user_id']} registered\n";
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

        // Send to receiver if online first for real-time delivery
        foreach ($this->clients as $client) {
            if (isset($this->users[spl_object_hash($client)]) && $this->users[spl_object_hash($client)] == $receiver_id) {
                $client->send(json_encode([
                    'type' => 'message',
                    'sender_id' => $sender_id,
                    'message' => $message,
                    'timestamp' => date('Y-m-d H:i:s')
                ]));
                break;
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

    protected function saveMessage($sender_id, $receiver_id, $message) {
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
