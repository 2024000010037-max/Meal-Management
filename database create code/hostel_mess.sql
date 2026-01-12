
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','manager','user') DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `nid_photo` varchar(255) DEFAULT NULL,
  `is_auto_meal` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


/*   Indexes for table `users` */
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

/* AUTO_INCREMENT for table `users` */
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `bazar` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `shopper_id` int(11) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `bazar_date` date DEFAULT NULL,
  `shopper_ids` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `bazar`
  ADD CONSTRAINT `bazar_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`);
ALTER TABLE `bazar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `bazar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manager_id` (`manager_id`);

CREATE TABLE `deposits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `deposit_date` date NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* Constraints for table deposits */
ALTER TABLE `deposits`
  ADD CONSTRAINT `deposits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `deposits_ibfk_2` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`);

ALTER TABLE `deposits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
