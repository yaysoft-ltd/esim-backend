INSERT INTO `flaggroups` (`id`, `flagGroupName`, `parentFlagGroupId`, `displayOrder`, `isActive`, `isDelete`, `created_at`, `updated_at`, `description`, `viewenable`) VALUES
  (1, 'General', NULL, NULL, 1, 0, '2023-03-30 16:30:00', '2023-03-30 16:30:00', '', 1),
  (2, 'Payments', NULL, NULL, 1, 0, '2023-03-30 16:30:00', '2023-03-30 16:30:00', 'Choose Payment Mode Whichever use in app', 1),
  (9, 'Airalo', NULL, NULL, 1, 0, '2023-03-30 16:38:07', '2023-03-30 16:38:07', '', 1),
  (11, 'Commission', 1, NULL, 1, 0, '2023-03-31 11:05:22', '2023-03-31 11:05:22', 'Include commission percentage of airalo price', 1),
  (12, 'Master Image', NULL, NULL, 1, 0, '2023-04-05 10:29:14', '2023-04-05 10:29:14', '', 1),
  (65, 'Timezone', NULL, NULL, 1, 0, '2025-08-21 10:29:14', '2025-08-21 10:29:14', 'Set timezone according your preference', 1),
  (66, 'Razorpay', 2, NULL, 1, 0, '2025-08-21 10:29:14', '2025-08-21 10:29:14', '', 1),
  (67, 'Stripe', 2, NULL, 1, 0, '2025-08-21 10:29:14', '2025-08-21 10:29:14', '', 1),
  (68, 'Cashfree', 2, NULL, 1, 0, '2025-08-21 10:29:14', '2025-08-21 10:29:14', '', 1),
  (69, 'Sync Data', NULL, NULL, 1, 0, '2025-08-27 10:29:14', '2025-08-27 10:29:14', 'Sync Package From Airalo', 1),
  (70, 'Web Config', NULL, NULL, 1, 0, '2025-08-27 10:29:14', '2025-08-27 10:29:14', 'Website Configuration', 1),
  (71, 'Firebase', 70, NULL, 1, 0, '2025-08-27 10:29:14', '2025-08-27 10:29:14', 'Firebase Configuration', 1),
  (72, 'Contact Details', 70, NULL, 1, 0, '2025-08-27 10:29:14', '2025-08-27 10:29:14', 'Set Contact Details', 1);
