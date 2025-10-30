<?php
require_once 'db.php';

try {
    // Check if fk_sender key exists
    $stmt = $pdo->query("SHOW KEYS FROM messages WHERE Key_name = 'fk_sender'");
    $fkSenderKeyExists = $stmt->rowCount() > 0;

    if (!$fkSenderKeyExists) {
        $pdo->exec("ALTER TABLE messages ADD KEY fk_sender (sender_id)");
        echo "Added fk_sender key.\n";
    } else {
        echo "fk_sender key already exists.\n";
    }

    // Check if fk_receiver key exists
    $stmt = $pdo->query("SHOW KEYS FROM messages WHERE Key_name = 'fk_receiver'");
    $fkReceiverKeyExists = $stmt->rowCount() > 0;

    if (!$fkReceiverKeyExists) {
        $pdo->exec("ALTER TABLE messages ADD KEY fk_receiver (receiver_id)");
        echo "Added fk_receiver key.\n";
    } else {
        echo "fk_receiver key already exists.\n";
    }

    // Check if AUTO_INCREMENT is set
    $stmt = $pdo->query("SHOW CREATE TABLE messages");
    $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
    if (strpos($createTable['Create Table'], 'AUTO_INCREMENT') === false) {
        $pdo->exec("ALTER TABLE messages MODIFY id int(11) NOT NULL AUTO_INCREMENT");
        echo "Set AUTO_INCREMENT on id.\n";
    } else {
        echo "AUTO_INCREMENT already set.\n";
    }

    // Check if fk_sender constraint exists
    $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'messages' AND CONSTRAINT_NAME = 'fk_sender'");
    $fkSenderConstraintExists = $stmt->rowCount() > 0;

    if (!$fkSenderConstraintExists) {
        $pdo->exec("ALTER TABLE messages ADD CONSTRAINT fk_sender FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE");
        echo "Added fk_sender constraint.\n";
    } else {
        echo "fk_sender constraint already exists.\n";
    }

    // Check if fk_receiver constraint exists
    $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'messages' AND CONSTRAINT_NAME = 'fk_receiver'");
    $fkReceiverConstraintExists = $stmt->rowCount() > 0;

    if (!$fkReceiverConstraintExists) {
        $pdo->exec("ALTER TABLE messages ADD CONSTRAINT fk_receiver FOREIGN KEY (receiver_id) REFERENCES users (id) ON DELETE CASCADE");
        echo "Added fk_receiver constraint.\n";
    } else {
        echo "fk_receiver constraint already exists.\n";
    }

    echo "Messages table check and update completed.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
