<?php
require_once 'db.php';

try {
    // Check if primary key exists
    $stmt = $pdo->query("SHOW KEYS FROM messages WHERE Key_name = 'PRIMARY'");
    $primaryKeyExists = $stmt->rowCount() > 0;

    if (!$primaryKeyExists) {
        // Add primary key if it doesn't exist
        $pdo->exec("ALTER TABLE messages ADD PRIMARY KEY (id)");
    }

    // Check if fk_sender key exists
    $stmt = $pdo->query("SHOW KEYS FROM messages WHERE Key_name = 'fk_sender'");
    $fkSenderExists = $stmt->rowCount() > 0;

    if (!$fkSenderExists) {
        $pdo->exec("ALTER TABLE messages ADD KEY fk_sender (sender_id)");
    }

    // Check if fk_receiver key exists
    $stmt = $pdo->query("SHOW KEYS FROM messages WHERE Key_name = 'fk_receiver'");
    $fkReceiverExists = $stmt->rowCount() > 0;

    if (!$fkReceiverExists) {
        $pdo->exec("ALTER TABLE messages ADD KEY fk_receiver (receiver_id)");
    }

    // Set AUTO_INCREMENT if not set
    $stmt = $pdo->query("SHOW CREATE TABLE messages");
    $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
    if (strpos($createTable['Create Table'], 'AUTO_INCREMENT') === false) {
        $pdo->exec("ALTER TABLE messages MODIFY id int(11) NOT NULL AUTO_INCREMENT");
    }

    // Check if fk_sender constraint exists
    $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'messages' AND CONSTRAINT_NAME = 'fk_sender'");
    $fkSenderConstraintExists = $stmt->rowCount() > 0;

    if (!$fkSenderConstraintExists) {
        $pdo->exec("ALTER TABLE messages ADD CONSTRAINT fk_sender FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE");
    }

    // Check if fk_receiver constraint exists
    $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'messages' AND CONSTRAINT_NAME = 'fk_receiver'");
    $fkReceiverConstraintExists = $stmt->rowCount() > 0;

    if (!$fkReceiverConstraintExists) {
        $pdo->exec("ALTER TABLE messages ADD CONSTRAINT fk_receiver FOREIGN KEY (receiver_id) REFERENCES users (id) ON DELETE CASCADE");
    }

    echo "Messages table updated successfully.";
} catch (Exception $e) {
    echo "Error updating messages table: " . $e->getMessage();
}
?>
