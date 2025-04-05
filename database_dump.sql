-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 05, 2025 at 08:25 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `onlinebookstore`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(100) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `author`, `isbn`, `price`, `description`, `publication_date`, `publisher`, `cover_image`, `stock_quantity`, `category_id`, `is_featured`) VALUES
(1, 'The Hobbit', 'J.R.R. Tolkien', '9780547928227', 12.99, 'Bilbo Baggins is a hobbit who enjoys a comfortable, unambitious life, rarely traveling any farther than his pantry or cellar. But his contentment is disturbed when the wizard Gandalf and a company of dwarves arrive on his doorstep.', NULL, 'Houghton Mifflin Harcourt', 'https://m.media-amazon.com/images/I/418jD+Rsd5L._SL500_.jpg', 50, 4, 0),
(2, 'Harry Potter and the Sorcerer\'s Stone', 'J.K. Rowling', '9780590353427', 14.99, 'Harry Potter has never been the star of a Quidditch team, scoring points while riding a broom far above the ground. He knows no spells, has never helped to hatch a dragon, and has never worn a cloak of invisibility. All he knows is a miserable life with the Dursleys, his horrible aunt and uncle, and their abominable son, Dudley.', NULL, 'Scholastic', 'https://m.media-amazon.com/images/I/517CL0lWQ-L._SL500_.jpg', 75, 4, 0),
(3, 'A Game of Thrones', 'George R.R. Martin', '9780553573404', 16.99, 'Long ago, in a time forgotten, a preternatural event threw the seasons out of balance. In a land where summers can last decades and winters a lifetime, trouble is brewing. The cold is returning, and in the frozen wastes to the north of Winterfell, sinister forces are massing beyond the kingdom\'s protective Wall.', NULL, 'Bantam Books', 'https://m.media-amazon.com/images/I/51Eon6XtIoL._SL500_.jpg', 40, 4, 0),
(4, 'The Shining', 'Stephen King', '9780307743657', 13.99, 'Jack Torrance\'s new job at the Overlook Hotel is the perfect chance for a fresh start. As the off-season caretaker at the atmospheric old hotel, he\'ll have plenty of time to spend reconnecting with his family and working on his writing.', NULL, 'Doubleday', 'https://m.media-amazon.com/images/I/51+m0dF3sRL._SL500_.jpg', 35, 5, 0),
(5, 'Dune', 'Frank Herbert', '9780441172719', 15.99, 'Set on the desert planet Arrakis, Dune is the story of the boy Paul Atreides, heir to a noble family tasked with ruling an inhospitable world where the only thing of value is the spice melange, a drug capable of extending life and enhancing consciousness.', NULL, 'Ace Books', 'https://m.media-amazon.com/images/I/41uBViwic5L._SL500_.jpg', 60, 3, 0),
(6, 'Pride and Prejudice', 'Jane Austen', '9780141439518', 9.99, 'Since its immediate success in 1813, Pride and Prejudice has remained one of the most popular novels in the English language. Jane Austen called this brilliant work her own darling child and its vivacious heroine, Elizabeth Bennet, as delightful a creature as ever appeared in print.', NULL, 'Penguin Classics', 'https://m.media-amazon.com/images/I/51G7Ie1hExL._SL500_.jpg', 30, 1, 0),
(7, 'The Lord of the Rings: The Fellowship of the Ring', 'J.R.R. Tolkien', '9780618346257', 18.99, 'In ancient times the Rings of Power were crafted by the Elven-smiths, and Sauron, the Dark Lord, forged the One Ring, filling it with his own power so that he could rule all others.', NULL, 'Houghton Mifflin Harcourt', 'https://m.media-amazon.com/images/I/41qCdBemr9L._SL500_.jpg', 45, 4, 0),
(8, 'It', 'Stephen King', '9781501142970', 17.99, 'Welcome to Derry, Maine. It\'s a small city, a place as hauntingly familiar as your own hometown. Only in Derry the haunting is real.', NULL, 'Scribner', 'https://m.media-amazon.com/images/I/41XTHy2g4WL._SL500_.jpg', 25, 5, 0),
(9, 'The Alchemist', 'Paulo Coelho', '9780062315007', 11.99, 'Paulo Coelho\'s enchanting novel has inspired a devoted following around the world. This story, dazzling in its powerful simplicity and soul-stirring wisdom, is about an Andalusian shepherd boy named Santiago who travels from his homeland in Spain to the Egyptian desert in search of a treasure buried near the Pyramids.', NULL, 'HarperOne', 'https://m.media-amazon.com/images/I/51bDuU2p5zL._SL500_.jpg', 80, 1, 0),
(10, 'The Great Gatsby', 'F. Scott Fitzgerald', '9780743273565', 8.99, 'The Great Gatsby, F. Scott Fitzgerald\'s third book, stands as the supreme achievement of his career. This exemplary novel of the Jazz Age has been acclaimed by generations of readers.', NULL, 'Scribner', 'https://m.media-amazon.com/images/I/41BiT3sOQBL._SL500_.jpg', 40, 1, 0),
(11, 'Little Women', 'Louisa May Alcott', '9780316489270', 10.99, 'Generations of readers young and old, male and female, have fallen in love with the March sisters of Louisa May Alcott\'s most popular and enduring novel, Little Women.', NULL, 'Little, Brown and Company', 'https://m.media-amazon.com/images/I/41uNRwCQK2S._SL500_.jpg', 35, 1, 0),
(12, 'The Midnight Library', 'Matt Haig', '9780525559474', 19.99, 'Between life and death there is a library, and within that library, the shelves go on forever. Every book provides a chance to try another life you could have lived.', NULL, 'Viking', 'https://m.media-amazon.com/images/I/51h5i-Wch7L._SL500_.jpg', 65, 1, 0),
(13, 'The Handmaid\'s Tale', 'Margaret Atwood', '9780385490818', 13.99, 'Set in the near future, it describes life in what was once the United States and is now called the Republic of Gilead, a monotheocracy that has reacted to social unrest and a sharply declining birthrate by reverting to, and going beyond, the repressive intolerance of the original Puritans.', NULL, 'Anchor', 'https://m.media-amazon.com/images/I/31zzmiNcUuL._SL500_.jpg', 30, 3, 0),
(14, 'Project Hail Mary', 'Andy Weir', '9780593135204', 22.99, 'Ryland Grace is the sole survivor on a desperate, last-chance mission—and if he fails, humanity and the earth itself will perish. Except that right now, he doesn\'t know that. He can\'t even remember his own name, let alone the nature of his assignment or how to complete it.', NULL, 'Ballantine Books', 'https://m.media-amazon.com/images/I/51J7+qmeY8L._SL500_.jpg', 50, 3, 0),
(15, 'h', 'h', 'hh', 1000.00, '', NULL, '', '', 3, 4, 0);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `user_id`, `book_id`, `quantity`, `date_added`) VALUES
(15, 2, 9, 1, '2025-04-02 05:25:12'),
(16, 2, 11, 1, '2025-04-02 06:14:59');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Fiction', 'General fiction books including classics and contemporary works'),
(2, 'Non-Fiction', 'Factual books based on real events, people, and information'),
(3, 'Science Fiction', 'Books about futuristic technology, space travel, and alien worlds'),
(4, 'Fantasy', 'Books featuring magic, mythical creatures, and supernatural elements'),
(5, 'Mystery & Thriller', 'Books focused on suspense, crime, and mystery solving'),
(6, 'Romance', 'Books centered on romantic relationships and love stories'),
(7, 'Young Adult', 'Books aimed at readers aged 12-18'),
(8, 'Children\'s Books', 'Books aimed at younger readers');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `user_id`, `is_read`, `created_at`) VALUES
(1, 'g', 'g@gmail.com', 'Product Information', 'g', 0, 0, '2025-03-27 18:25:25');

-- --------------------------------------------------------

--
-- Table structure for table `hero_banners`
--

CREATE TABLE `hero_banners` (
  `banner_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `button_text` varchar(50) DEFAULT 'Shop Now',
  `button_link` varchar(255) DEFAULT 'special_offers.php',
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hero_banners`
--

INSERT INTO `hero_banners` (`banner_id`, `title`, `subtitle`, `image_url`, `button_text`, `button_link`, `is_active`, `display_order`) VALUES
(1, 'Spring Reading Sale', 'Discover your next favorite book with 30% off selected titles', '../images/banners/spring_sale.jpg', 'Shop Now', 'special_offers.php', 1, 1),
(2, 'New Arrivals', 'Fresh titles from your favorite authors', '../images/banners/new_arrivals.jpg', 'Explore', 'new_releases.php', 1, 2),
(3, 'Bestsellers Collection', 'Readers\' favorites all in one place', '../images/banners/bestsellers.jpg', 'View Collection', 'bestsellers.php', 1, 3),
(4, 'New Arrivals', 'Fresh titles from your favorite authors', '../images/banners/new_arrivals.jpg', 'Explore', 'new_releases.php', 1, 2),
(5, 'Bestsellers Collection', 'Readers\' favorites all in one place', '../images/banners/bestsellers.jpg', 'View Collection', 'bestsellers.php', 1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','completed','canceled') DEFAULT 'pending',
  `reference_code` varchar(20) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Credit Card',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_date`, `status`, `reference_code`, `qr_code`, `shipping_fee`, `payment_method`, `total_amount`) VALUES
(3, 2, '2025-03-27 10:51:14', '', 'HB-000003', '/images/qrcodes/order_3.png', 5.00, 'Credit Card', 33.98),
(4, 2, '2025-03-27 10:51:43', '', 'HB-000004', '/images/qrcodes/order_4.png', 5.00, 'Credit Card', 17.99),
(5, 2, '2025-03-27 10:51:57', '', 'HB-000005', '/images/qrcodes/order_5.png', 5.00, 'PayPal', 19.99),
(6, 1, '2025-03-27 11:01:11', '', 'HB-000006', NULL, 5.00, 'Credit Card', 30.98),
(7, 1, '2025-03-27 11:01:35', '', 'HB-000007', NULL, 5.00, 'PayPal', 19.99);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `book_id`, `quantity`, `price`) VALUES
(4, 3, 5, 1, 15.99),
(5, 3, 1, 1, 12.99),
(6, 4, 1, 1, 12.99),
(7, 5, 2, 1, 14.99),
(8, 6, 1, 2, 12.99),
(9, 7, 2, 1, 14.99);

-- --------------------------------------------------------

--
-- Table structure for table `temp_checkout`
--

CREATE TABLE `temp_checkout` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `data` text NOT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `temp_checkout`
--

INSERT INTO `temp_checkout` (`id`, `user_id`, `token`, `data`, `created_at`, `expires_at`, `used`) VALUES
(1, 1, '6523e44a56926a90d8da142cbbfeda6e', '{\"user_id\":1,\"items\":[{\"book_id\":3,\"title\":\"A Game of Thrones\",\"quantity\":1,\"price\":\"16.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":2,\"price\":\"12.99\"},{\"book_id\":2,\"title\":\"Harry Potter and the Sorcerer\'s Stone\",\"quantity\":1,\"price\":\"14.99\"}],\"total\":57.96,\"timestamp\":1743070752}', '2025-03-27 17:19:12', '2025-03-27 17:49:12', 0),
(2, 1, 'e79162dac391444dfc97ab938ad73912', '{\"user_id\":1,\"items\":[{\"book_id\":3,\"title\":\"A Game of Thrones\",\"quantity\":2,\"price\":\"16.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":2,\"price\":\"12.99\"},{\"book_id\":2,\"title\":\"Harry Potter and the Sorcerer\'s Stone\",\"quantity\":2,\"price\":\"14.99\"}],\"total\":89.94,\"timestamp\":1743070754}', '2025-03-27 17:19:14', '2025-03-27 17:49:14', 0),
(3, 1, 'e79162dac391444dfc97ab938ad73912', '{\"user_id\":1,\"items\":[{\"book_id\":3,\"title\":\"A Game of Thrones\",\"quantity\":2,\"price\":\"16.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":2,\"price\":\"12.99\"},{\"book_id\":2,\"title\":\"Harry Potter and the Sorcerer\'s Stone\",\"quantity\":2,\"price\":\"14.99\"}],\"total\":89.94,\"timestamp\":1743070754}', '2025-03-27 17:19:14', '2025-03-27 17:49:14', 0),
(4, 1, '9f4b9cea9d575f3fe7c14d826bc31a78', '{\"user_id\":1,\"items\":[{\"book_id\":3,\"title\":\"A Game of Thrones\",\"quantity\":2,\"price\":\"16.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":2,\"price\":\"12.99\"},{\"book_id\":2,\"title\":\"Harry Potter and the Sorcerer\'s Stone\",\"quantity\":3,\"price\":\"14.99\"}],\"total\":104.92999999999999,\"timestamp\":1743070758}', '2025-03-27 17:19:18', '2025-03-27 17:49:18', 0),
(5, 1, 'a9dfc98b5c378dfeac20562a4f917331', '{\"user_id\":1,\"items\":[{\"book_id\":3,\"title\":\"A Game of Thrones\",\"quantity\":2,\"price\":\"16.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":2,\"price\":\"12.99\"},{\"book_id\":2,\"title\":\"Harry Potter and the Sorcerer\'s Stone\",\"quantity\":3,\"price\":\"14.99\"}],\"total\":104.92999999999999,\"timestamp\":1743070761}', '2025-03-27 17:19:21', '2025-03-27 17:49:21', 0),
(6, 1, 'dbfa23cb823ffd917dc0874a0039c9c8', '{\"user_id\":1,\"items\":[{\"book_id\":3,\"title\":\"A Game of Thrones\",\"quantity\":2,\"price\":\"16.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":2,\"price\":\"12.99\"},{\"book_id\":2,\"title\":\"Harry Potter and the Sorcerer\'s Stone\",\"quantity\":3,\"price\":\"14.99\"}],\"total\":104.92999999999999,\"timestamp\":1743070766}', '2025-03-27 17:19:26', '2025-03-27 17:49:26', 0),
(7, 1, '1fa3126d4333bfac8ea2310b06416f43', '{\"user_id\":1,\"items\":[{\"book_id\":3,\"title\":\"A Game of Thrones\",\"quantity\":2,\"price\":\"16.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":2,\"price\":\"12.99\"}],\"total\":59.959999999999994,\"timestamp\":1743070780}', '2025-03-27 17:19:40', '2025-03-27 17:49:40', 0),
(8, 1, '153b004ec018d0515edfe650ea258a36', '{\"user_id\":1,\"items\":[{\"book_id\":3,\"title\":\"A Game of Thrones\",\"quantity\":2,\"price\":\"16.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":2,\"price\":\"12.99\"}],\"total\":59.959999999999994,\"timestamp\":1743070783}', '2025-03-27 17:19:43', '2025-03-27 17:49:43', 0),
(9, 1, '3c10d932cb1612d02d9e8e6af967db18', '{\"user_id\":1,\"items\":[{\"book_id\":3,\"title\":\"A Game of Thrones\",\"quantity\":2,\"price\":\"16.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":2,\"price\":\"12.99\"}],\"total\":59.959999999999994,\"timestamp\":1743070822}', '2025-03-27 17:20:22', '2025-03-27 17:50:22', 0),
(10, 1, 'a67d263065ad8efb9b501ff041714d2a', '{\"user_id\":1,\"items\":[{\"book_id\":3,\"title\":\"A Game of Thrones\",\"quantity\":2,\"price\":\"16.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":2,\"price\":\"12.99\"}],\"total\":59.959999999999994,\"timestamp\":1743070834}', '2025-03-27 17:20:34', '2025-03-27 17:50:34', 0),
(11, 1, '501ad8f0ab1027e316e0c72f31db6c24', '{\"user_id\":1,\"items\":[{\"book_id\":2,\"title\":\"Harry Potter and the Sorcerer\'s Stone\",\"quantity\":1,\"price\":\"14.99\"},{\"book_id\":3,\"title\":\"A Game of Thrones\",\"quantity\":2,\"price\":\"16.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":2,\"price\":\"12.99\"}],\"total\":74.95,\"timestamp\":1743071035}', '2025-03-27 17:23:55', '2025-03-27 17:53:55', 0),
(12, 2, '02886eae67ae825ebb0eec1b544be9a3', '{\"user_id\":2,\"items\":[{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":1,\"price\":\"12.99\"}],\"total\":12.99,\"timestamp\":1743071652}', '2025-03-27 17:34:12', '2025-03-27 18:04:12', 0),
(13, 2, 'b5cb6acc39cbc7cb0168d04b7f28be25', '{\"user_id\":2,\"items\":[{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":1,\"price\":\"12.99\"}],\"total\":12.99,\"timestamp\":1743071655}', '2025-03-27 17:34:15', '2025-03-27 18:04:15', 0),
(14, 2, '05d1e5cfa2627a4264803f885d8a941d', '{\"user_id\":2,\"items\":[{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":1,\"price\":\"12.99\"}],\"total\":12.99,\"timestamp\":1743072012}', '2025-03-27 17:40:12', '2025-03-27 18:10:12', 0),
(15, 2, '407d927ddcf97f044a40b9a5e98365c3', '{\"user_id\":2,\"items\":[{\"book_id\":5,\"title\":\"Dune\",\"quantity\":1,\"price\":\"15.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":1,\"price\":\"12.99\"}],\"total\":28.98,\"timestamp\":1743072296}', '2025-03-27 17:44:56', '2025-03-27 18:14:56', 0),
(16, 2, '0ae9fc34980222131d95ac8d5986d695', '{\"user_id\":2,\"items\":[{\"book_id\":5,\"title\":\"Dune\",\"quantity\":1,\"price\":\"15.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":1,\"price\":\"12.99\"}],\"total\":28.98,\"timestamp\":1743072298}', '2025-03-27 17:44:58', '2025-03-27 18:14:58', 0),
(17, 2, '6a1b53f111ceda26712e22305b0dc716', '{\"user_id\":2,\"items\":[{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":1,\"price\":\"12.99\"}],\"total\":12.99,\"timestamp\":1743072698}', '2025-03-27 17:51:38', '2025-03-27 18:21:38', 0),
(18, 2, '79355ec7d2cee3fd681fdd83447f5747', '{\"user_id\":2,\"items\":[{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":1,\"price\":\"12.99\"}],\"total\":12.99,\"timestamp\":1743072700}', '2025-03-27 17:51:40', '2025-03-27 18:21:40', 0),
(19, 2, '3c2d5d2c30fc640f9b8e3ff6268db0e5', '{\"user_id\":2,\"items\":[{\"book_id\":2,\"title\":\"Harry Potter and the Sorcerer\'s Stone\",\"quantity\":1,\"price\":\"14.99\"}],\"total\":14.99,\"timestamp\":1743072709}', '2025-03-27 17:51:49', '2025-03-27 18:21:49', 0),
(20, 2, '648feb8840d6ee0bdb599a60289b450b', '{\"user_id\":2,\"items\":[{\"book_id\":2,\"title\":\"Harry Potter and the Sorcerer\'s Stone\",\"quantity\":1,\"price\":\"14.99\"}],\"total\":14.99,\"timestamp\":1743072712}', '2025-03-27 17:51:52', '2025-03-27 18:21:52', 0),
(21, 1, '8a2a4576a84223e736abbad06fe047ce', '{\"user_id\":1,\"items\":[{\"book_id\":5,\"title\":\"Dune\",\"quantity\":1,\"price\":\"15.99\"},{\"book_id\":2,\"title\":\"Harry Potter and the Sorcerer\'s Stone\",\"quantity\":1,\"price\":\"14.99\"},{\"book_id\":3,\"title\":\"A Game of Thrones\",\"quantity\":2,\"price\":\"16.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":2,\"price\":\"12.99\"}],\"total\":90.94,\"timestamp\":1743073246}', '2025-03-27 18:00:46', '2025-03-27 18:30:46', 0),
(22, 1, '997062c3092994e631285f509e755db7', '{\"user_id\":1,\"items\":[{\"book_id\":5,\"title\":\"Dune\",\"quantity\":1,\"price\":\"15.99\"},{\"book_id\":2,\"title\":\"Harry Potter and the Sorcerer\'s Stone\",\"quantity\":1,\"price\":\"14.99\"},{\"book_id\":3,\"title\":\"A Game of Thrones\",\"quantity\":2,\"price\":\"16.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":2,\"price\":\"12.99\"}],\"total\":90.94,\"timestamp\":1743073248}', '2025-03-27 18:00:48', '2025-03-27 18:30:48', 0),
(23, 1, 'aef1d65f7991a60bb983b40d5d995c07', '{\"user_id\":1,\"items\":[{\"book_id\":2,\"title\":\"Harry Potter and the Sorcerer\'s Stone\",\"quantity\":1,\"price\":\"14.99\"},{\"book_id\":3,\"title\":\"A Game of Thrones\",\"quantity\":2,\"price\":\"16.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":2,\"price\":\"12.99\"}],\"total\":74.95,\"timestamp\":1743073255}', '2025-03-27 18:00:55', '2025-03-27 18:30:55', 0),
(24, 1, '7fd02f56f9092b10804305f1476a0229', '{\"user_id\":1,\"items\":[{\"book_id\":3,\"title\":\"A Game of Thrones\",\"quantity\":2,\"price\":\"16.99\"},{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":2,\"price\":\"12.99\"}],\"total\":59.959999999999994,\"timestamp\":1743073256}', '2025-03-27 18:00:56', '2025-03-27 18:30:56', 0),
(25, 1, '3406f694fe2ed3f6864f31d9b4d4fe78', '{\"user_id\":1,\"items\":[{\"book_id\":1,\"title\":\"The Hobbit\",\"quantity\":2,\"price\":\"12.99\"}],\"total\":25.98,\"timestamp\":1743073257}', '2025-03-27 18:00:57', '2025-03-27 18:30:57', 0),
(26, 1, '288041b00acd70ec5d4f55b18d565bde', '{\"user_id\":1,\"items\":[{\"book_id\":2,\"title\":\"Harry Potter and the Sorcerer\'s Stone\",\"quantity\":1,\"price\":\"14.99\"}],\"total\":14.99,\"timestamp\":1743073289}', '2025-03-27 18:01:29', '2025-03-27 18:31:29', 0),
(27, 1, '027d408b978158fcfa6a43b419488a77', '{\"user_id\":1,\"items\":[{\"book_id\":2,\"title\":\"Harry Potter and the Sorcerer\'s Stone\",\"quantity\":1,\"price\":\"14.99\"}],\"total\":14.99,\"timestamp\":1743073292}', '2025-03-27 18:01:32', '2025-03-27 18:31:32', 0),
(28, 2, 'df69ca0dd56a94b96e6ee46bb0e6ed68', '{\"user_id\":2,\"items\":[{\"book_id\":9,\"title\":\"The Alchemist\",\"quantity\":1,\"price\":\"11.99\"}],\"total\":11.99,\"timestamp\":1743571513}', '2025-04-02 12:25:13', '2025-04-02 12:55:13', 0),
(29, 2, 'ff027739b0e02ae186e1c6c29b8bb99f', '{\"user_id\":2,\"items\":[{\"book_id\":11,\"title\":\"Little Women\",\"quantity\":1,\"price\":\"10.99\"},{\"book_id\":9,\"title\":\"The Alchemist\",\"quantity\":1,\"price\":\"11.99\"}],\"total\":22.98,\"timestamp\":1743574499}', '2025-04-02 13:14:59', '2025-04-02 13:44:59', 0),
(30, 2, 'acbe0dfc55d10e099b125cc6b6f1da66', '{\"user_id\":2,\"items\":[{\"book_id\":11,\"title\":\"Little Women\",\"quantity\":1,\"price\":\"10.99\"},{\"book_id\":9,\"title\":\"The Alchemist\",\"quantity\":1,\"price\":\"11.99\"}],\"total\":22.98,\"timestamp\":1743574502}', '2025-04-02 13:15:02', '2025-04-02 13:45:02', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `full_name` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `name`, `address`, `phone`, `role`, `registration_date`, `full_name`, `city`, `state`, `zip_code`) VALUES
(1, 'admin123', '$2y$10$U4Wuyikn7z.mqkcnyY6E3ey360D1sN.WVDT8mHVSpr7iGqs/47xka', 'admin@bookstore.com', 'admin123', '3', '0987654321', 'admin', '2025-03-24 03:10:44', NULL, 'hh', 'hh', '555555'),
(2, 'duy123', '$2y$10$U747wrZugUefJktRmOZ3..X7eH1imf8wO6qmRmOo9TmI9dBP8bkGW', 'duy6902@h.com', 'Do Hoang Thai Duy', '123 Street', '0987654321', 'user', '2025-03-24 01:45:25', NULL, 'hh', 'hh', '444444'),
(5, 'h0001', '$2y$10$cg.zKl4UbSStiRHoiZbqdOfTiK79g.nKahQf803e9NfRI0t.d2pnO', 'duydhtth00929@fpt.edu.vn', 'Do Hoang Thai Duy', '123 Street, Ha noi', '0987654321', 'user', '2025-03-26 07:23:32', NULL, NULL, NULL, NULL),
(6, 'duy999', '$2y$10$2RKMPmb8nJqEDBLB8dNrXeWYt3G3TX8U5t6bwvNXlvg51SIz5KEOW', 'duygraphics@gmail.com', 'Duy do Hoang Tha', '123 street, ha noi', '0123456789', 'user', '2025-03-27 10:11:22', NULL, NULL, NULL, NULL),
(7, '123', '$2y$10$6NbnDEs5xmaCq/lJTJ1Lmenb1.gjxf39Pv8j2ew8fHilf0Zzfal1a', 'duy123@gmail.com', 'Duy dht', 'ha noi', '078634678', 'user', '2025-03-27 10:15:02', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `added_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `isbn` (`isbn`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `hero_banners`
--
ALTER TABLE `hero_banners`
  ADD PRIMARY KEY (`banner_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `temp_checkout`
--
ALTER TABLE `temp_checkout`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hero_banners`
--
ALTER TABLE `hero_banners`
  MODIFY `banner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `temp_checkout`
--
ALTER TABLE `temp_checkout`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`);

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
