-- Corrected ALTER statements for messages table
-- Removed ADD PRIMARY KEY since it already exists

ALTER TABLE `messages`
  ADD KEY `fk_sender` (`sender_id`),
  ADD KEY `fk_receiver` (`receiver_id`);

ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `messages`
  ADD CONSTRAINT `fk_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
