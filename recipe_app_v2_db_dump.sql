CREATE DATABASE  IF NOT EXISTS `recipe_app_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `recipe_app_db`;
-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: recipe_app_db
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (6,'Dairy-free'),(7,'Egg-free'),(19,'Gluten-free'),(14,'Healthy'),(15,'Nut-free'),(16,'Pregnancy-friendly'),(4,'Vegan'),(5,'Vegetarian');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favourites`
--

DROP TABLE IF EXISTS `favourites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `favourites` (
  `user_id` int(11) NOT NULL,
  `recipe_id` int(11) NOT NULL,
  `saved_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`,`recipe_id`),
  KEY `idx_fav_recipe` (`recipe_id`),
  CONSTRAINT `fk_fav_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`recipe_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favourites`
--

LOCK TABLES `favourites` WRITE;
/*!40000 ALTER TABLE `favourites` DISABLE KEYS */;
INSERT INTO `favourites` VALUES (10,1,'2026-04-20 19:32:00'),(10,2,'2026-04-25 08:15:00'),(10,5,'2026-05-01 21:04:00'),(10,7,'2026-05-04 14:22:00'),(10,9,'2026-05-07 12:50:00');
/*!40000 ALTER TABLE `favourites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingredients`
--

DROP TABLE IF EXISTS `ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ingredients` (
  `ingredient_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `default_unit` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`ingredient_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=258 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingredients`
--

LOCK TABLES `ingredients` WRITE;
/*!40000 ALTER TABLE `ingredients` DISABLE KEYS */;
INSERT INTO `ingredients` VALUES (1,'Olive oil','tbsp'),(4,'Garlic cloves','qty'),(6,'Chopped tomatoes','tin'),(8,'Dried spaghetti','g'),(9,'Self-raising flour','g'),(10,'Caster sugar','tbsp'),(11,'Baking powder','tsp'),(12,'Sea salt','pinch'),(13,'Soya milk','ml'),(14,'Vanilla extract','tsp'),(15,'Sunflower oil','tsp'),(17,'Plain yoghurt','g'),(19,'Courgette','qty'),(20,'Red onion','qty'),(21,'Extra virgin olive oil','tbsp'),(22,'Dried chilli flakes','tsp'),(25,'Dried oregano','tsp'),(26,'Vegetable oil','tbsp'),(30,'Kashmiri red chilli powder','tsp'),(31,'Ground cumin','tsp'),(33,'Lime juice','qty'),(36,'Green chillies','qty'),(38,'Double cream','tbsp'),(39,'Milk','tbsp'),(40,'Saffron strands','tsp'),(41,'Basmati rice','g'),(42,'Pomegranate seeds','tbsp'),(44,'Couscous','g'),(46,'Dried cranberries','g'),(47,'Pine nuts','g'),(49,'Flatleaf parsley','g'),(50,'Red wine vinegar','tbsp'),(51,'Rocket leaves','g'),(52,'Vanilla essence','drops'),(53,'Free-range eggs','qty'),(54,'Plain flour','tbsp'),(55,'Butter','g'),(56,'Plums','g'),(57,'Brown sugar','tbsp'),(58,'Flaked almonds','g'),(59,'Icing sugar','g'),(61,'Digestive biscuits','g'),(62,'Ground cardamom','tsp'),(64,'Powdered gelatine','tbsp'),(65,'Cream cheese','g'),(76,'Rose harissa','tbsp'),(77,'Lemon juice','qty'),(78,'White wine vinegar','tsp'),(79,'Dried mint','tsp'),(80,'Oyster mushrooms','g'),(81,'Garlic oil','tsp'),(82,'Sweet paprika','tsp'),(83,'Celery salt','tsp'),(84,'Garlic granules','tsp'),(85,'White pitta breads','qty'),(86,'White cabbage','qty'),(87,'Smoked streaky bacon','rashers'),(88,'Red wine','ml'),(89,'Antipasti marinated mushrooms','g'),(90,'Bay leaves','qty'),(91,'Dried thyme','tsp'),(92,'Balsamic vinegar','drizzle'),(93,'Sun-dried tomato halves','qty'),(94,'Parmesan','g'),(95,'Ground coriander','heaped tsp'),(97,'Pickled chillies','pc'),(114,'Onions','large'),(115,'Lean minced beef','kg'),(116,'Salt and black pepper','to taste'),(117,'Fresh basil','handful'),(148,'Self-raising brown or wholemeal flour','g'),(149,'Fine sea salt','pinch'),(150,'Full-fat plain yoghurt','g'),(151,'Yellow or orange pepper','pc'),(152,'Ready-grated mozzarella or cheddar','g'),(153,'Black pepper','to taste'),(154,'Fresh basil leaves','handful'),(155,'Passata sauce','tbsp'),(161,'Greek or natural yoghurt','g'),(162,'Finely grated ginger','tbsp'),(163,'Finely grated garlic','tbsp'),(164,'Ground cardamom seeds','tsp'),(165,'Coriander leaves and stalks','g'),(166,'Mint leaves','g'),(167,'Boneless lamb tenderloin or leg','g'),(168,'Full-fat milk','tbsp'),(180,'Small preserved lemons','pc'),(181,'Unsalted pistachio nuts','g'),(182,'Salt','tsp'),(225,'Double cream (to serve)','to serve'),(226,'Granulated sugar (base)','g'),(227,'Unsalted butter (melted)','g'),(228,'Sea salt (base)','pinch'),(229,'Granulated sugar (filling)','g'),(230,'Alfonso mango pulp (850g tin)','tin'),(231,'Sea salt (filling)','pinch'),(237,'Chopped tomatoes (400g tin)','tin'),(238,'Onion (white)','pc'),(239,'Salt and black pepper (yoghurt)','to taste'),(240,'Black pepper (doner spice)','tsp'),(241,'Fresh tomatoes (garnish)','pc');
/*!40000 ALTER TABLE `ingredients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ratings` (
  `rating_id` int(11) NOT NULL AUTO_INCREMENT,
  `recipe_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `overall_rating` tinyint(4) NOT NULL CHECK (`overall_rating` between 1 and 5),
  `taste_rating` tinyint(4) DEFAULT NULL CHECK (`taste_rating` between 1 and 5),
  `difficulty_rating` tinyint(4) DEFAULT NULL CHECK (`difficulty_rating` between 1 and 5),
  `review_text` text DEFAULT NULL,
  `aesthetics_rating` tinyint(4) DEFAULT NULL CHECK (`aesthetics_rating` between 1 and 5),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`rating_id`),
  UNIQUE KEY `uk_ratings_user_recipe` (`recipe_id`,`user_id`),
  KEY `fk_ratings_user` (`user_id`),
  CONSTRAINT `fk_ratings_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`recipe_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ratings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ratings`
--

LOCK TABLES `ratings` WRITE;
/*!40000 ALTER TABLE `ratings` DISABLE KEYS */;
INSERT INTO `ratings` VALUES (1,1,10,5,5,3,'My family loves this recipe. The sun-dried tomatoes really make it.',4,'2026-04-21 20:15:00'),(2,2,10,4,4,2,'So easy, perfect for a Sunday morning.',4,'2026-04-26 09:30:00'),(3,5,10,5,5,4,NULL,5,'2026-05-02 19:00:00'),(4,9,10,4,5,2,'Surprisingly meaty texture for a veggie dish.',3,'2026-05-07 13:00:00'),(5,1,2,4,5,3,'Great weeknight option. I added a bit more garlic.',4,'2026-04-15 18:45:00'),(6,2,5,5,4,1,'Fluffiest vegan pancakes I have made.',5,'2026-04-22 09:00:00'),(7,3,6,3,4,2,'Quick and tasty but base was a bit dense.',3,'2026-04-28 19:15:00'),(8,5,4,5,5,4,'Restaurant quality. Worth the marinating time.',5,'2026-05-03 20:30:00'),(9,6,7,4,4,1,NULL,5,'2026-04-30 12:20:00'),(10,7,8,5,5,3,'Beautifully simple French dessert.',4,'2026-05-05 21:00:00'),(11,8,9,4,5,4,'A real centrepiece. Made it for a birthday and it was a hit.',5,'2026-05-06 22:10:00');
/*!40000 ALTER TABLE `ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipe_categories`
--

DROP TABLE IF EXISTS `recipe_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe_categories` (
  `recipe_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`recipe_id`,`category_id`),
  KEY `fk_rc_category` (`category_id`),
  CONSTRAINT `fk_rc_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rc_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`recipe_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe_categories`
--

LOCK TABLES `recipe_categories` WRITE;
/*!40000 ALTER TABLE `recipe_categories` DISABLE KEYS */;
INSERT INTO `recipe_categories` VALUES (1,7),(1,15),(2,4),(2,5),(2,6),(2,7),(2,16),(3,5),(3,7),(3,14),(3,15),(3,16),(5,7),(5,16),(5,19),(6,4),(6,5),(6,7),(7,5),(8,7),(8,15),(9,5),(9,7),(9,14),(9,15),(9,16);
/*!40000 ALTER TABLE `recipe_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipe_ingredients`
--

DROP TABLE IF EXISTS `recipe_ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe_ingredients` (
  `recipe_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`recipe_id`,`ingredient_id`),
  KEY `fk_ri_ingredient` (`ingredient_id`),
  CONSTRAINT `fk_ri_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`ingredient_id`),
  CONSTRAINT `fk_ri_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`recipe_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe_ingredients`
--

LOCK TABLES `recipe_ingredients` WRITE;
/*!40000 ALTER TABLE `recipe_ingredients` DISABLE KEYS */;
INSERT INTO `recipe_ingredients` VALUES (1,1,2.00,'tbsp'),(1,4,3.00,'cloves'),(1,6,800.00,'g'),(1,8,900.00,'g'),(1,25,1.00,'tsp'),(1,87,6.00,'rashers'),(1,88,500.00,'ml'),(1,89,290.00,'g'),(1,90,2.00,'pc'),(1,91,1.00,'tsp'),(1,92,1.00,'drizzle'),(1,93,13.00,'pc'),(1,94,1.00,'to serve'),(1,114,2.00,'large'),(1,115,1.00,'kg'),(1,116,1.00,'to taste'),(1,117,1.00,'handful'),(2,9,125.00,'g'),(2,10,2.00,'tbsp'),(2,11,1.00,'tsp'),(2,12,1.00,'pinch'),(2,13,150.00,'ml'),(2,14,0.25,'tsp'),(2,15,4.00,'tsp'),(3,19,1.00,'pc'),(3,20,1.00,'pc'),(3,21,1.00,'tbsp'),(3,22,0.50,'tsp'),(3,25,1.00,'tsp'),(3,148,125.00,'g'),(3,149,1.00,'pinch'),(3,150,125.00,'g'),(3,151,1.00,'pc'),(3,152,50.00,'g'),(3,153,1.00,'to taste'),(3,154,1.00,'optional'),(3,155,6.00,'tbsp'),(5,12,4.00,'tsp'),(5,26,5.00,'tbsp'),(5,30,2.00,'tsp'),(5,31,5.00,'tsp'),(5,33,1.00,'pc'),(5,36,4.00,'pc'),(5,38,4.00,'tbsp'),(5,40,1.00,'tsp'),(5,41,400.00,'g'),(5,42,2.00,'optional'),(5,114,2.00,'pc'),(5,161,200.00,'g'),(5,162,4.00,'tbsp'),(5,163,3.00,'tbsp'),(5,164,1.00,'tsp'),(5,165,30.00,'g'),(5,166,30.00,'g'),(5,167,800.00,'g'),(5,168,1.50,'tbsp'),(6,1,125.00,'ml'),(6,4,4.00,'pc'),(6,20,1.00,'pc'),(6,44,225.00,'g'),(6,46,180.00,'g'),(6,47,120.00,'g'),(6,49,60.00,'g'),(6,50,4.00,'tbsp'),(6,51,80.00,'g'),(6,180,8.00,'pc'),(6,181,160.00,'g'),(6,182,1.00,'tsp'),(7,10,170.00,'g'),(7,38,125.00,'ml'),(7,39,125.00,'ml'),(7,52,3.00,'drop'),(7,53,4.00,'pc'),(7,54,1.00,'tbsp'),(7,55,30.00,'g'),(7,56,500.00,'g'),(7,57,2.00,'tbsp'),(7,58,30.00,'optional'),(7,59,1.00,'dusting'),(7,225,1.00,'to serve'),(8,38,120.00,'ml'),(8,61,280.00,'g'),(8,62,0.25,'tsp'),(8,64,2.25,'tbsp'),(8,65,115.00,'g'),(8,226,65.00,'g'),(8,227,128.00,'g'),(8,228,1.00,'large pinch'),(8,229,100.00,'g'),(8,230,1.00,'tin'),(8,231,1.00,'large pinch'),(9,10,2.00,'tsp'),(9,17,150.00,'g'),(9,49,20.00,'g'),(9,76,2.00,'tbsp'),(9,77,1.00,'squeeze'),(9,78,2.00,'tsp'),(9,79,1.00,'heaped tsp'),(9,80,500.00,'g'),(9,81,2.00,'tsp'),(9,82,2.00,'tsp'),(9,83,2.00,'tsp'),(9,84,3.00,'tsp'),(9,85,4.00,'pc'),(9,86,0.25,'pc'),(9,95,2.00,'heaped tsp'),(9,97,6.00,'optional'),(9,237,1.00,'tin'),(9,238,1.00,'pc'),(9,239,1.00,'to taste'),(9,240,0.50,'tsp'),(9,241,2.00,'pc');
/*!40000 ALTER TABLE `recipe_ingredients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipe_steps`
--

DROP TABLE IF EXISTS `recipe_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe_steps` (
  `step_id` int(11) NOT NULL AUTO_INCREMENT,
  `recipe_id` int(11) NOT NULL,
  `step_number` int(11) NOT NULL,
  `instruction` text NOT NULL,
  `time_minutes` int(11) DEFAULT 0,
  PRIMARY KEY (`step_id`),
  KEY `fk_steps_recipe` (`recipe_id`),
  CONSTRAINT `fk_steps_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`recipe_id`) ON DELETE CASCADE,
  CONSTRAINT `chk_step_num` CHECK (`step_number` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe_steps`
--

LOCK TABLES `recipe_steps` WRITE;
/*!40000 ALTER TABLE `recipe_steps` DISABLE KEYS */;
INSERT INTO `recipe_steps` VALUES (57,1,1,'Heat the oil in a large, heavy-based saucepan and fry the bacon until golden over a medium heat. Add the onions and garlic, frying until softened. Increase the heat and add the minced beef. Fry it until it has browned, breaking down any chunks of meat with a wooden spoon. Pour in the wine and boil until it has reduced in volume by about a third. Reduce the temperature and stir in the tomatoes, drained mushrooms, bay leaves, oregano, thyme and balsamic vinegar.',25),(58,1,2,'Either blitz the sun-dried tomatoes in a small blender with a little of the oil to loosen, or just finely chop before adding to the pan. Season well with salt and pepper. Cover with a lid and simmer the sauce over a gentle heat for 1-1½ hours until it’s rich and thickened, stirring occasionally. At the end of the cooking time, stir in the basil and add any extra seasoning if necessary.',80),(59,1,3,'Remove from the heat to \'settle\' while you cook the spaghetti in plenty of boiling salted water (for the time stated on the packet). Drain and divide between warmed plates. Scatter a little parmesan over the spaghetti before adding a good ladleful of the sauce, finishing with a scattering of more cheese and a twist of black pepper.',15),(60,2,1,'Put the flour, sugar, baking powder and salt in a bowl and mix thoroughly. Add the milk and vanilla extract and mix with a whisk until smooth.',5),(61,2,2,'Place a large non-stick frying pan over a medium heat. Add 2 teaspoons of the oil and wipe around the pan with a heatproof brush or carefully using a thick wad of kitchen paper.',2),(62,2,3,'Once the pan is hot, pour a small ladleful (around two dessert spoons) of the batter into one side of the pan and spread with the back of the spoon until around 10cm/4in in diameter. Make a second pancake in exactly the same way, greasing the pan with the remaining oil before adding the batter.',5),(63,2,4,'Cook for about a minute, or until bubbles are popping on the surface and just the edges look dry and slightly shiny. Quickly and carefully flip over and cook on the other side for a further minute, or until light, fluffy and pale golden brown. If you turn the pancakes too late, they will be too set to rise evenly. You can always flip again if you need the first side to go a little browner.',5),(64,2,5,'Transfer to a plate and keep warm in a single layer (so they don’t get squished) on a baking tray in a low oven while the rest of the pancakes are cooked in exactly the same way. Serve with your preferred toppings.',5),(65,3,1,'Preheat the oven to 220C/200C Fan/Gas 7.',5),(66,3,2,'To prepare the topping, put the pepper, courgette, red onion and oil in a bowl, season with lots of black pepper and mix together. Scatter the vegetables over a large baking tray and roast for 15 minutes.',15),(67,3,3,'Meanwhile, make the pizza base. Mix the flour and salt in a large bowl. Add the yoghurt and 1 tablespoon of cold water and mix with a spoon, then use your hands to form a soft, spongy dough. Turn out onto a lightly floured surface and knead for about 1 minute.',5),(68,3,4,'Using a floured rolling pin, roll the dough into a roughly oval shape, approx. 3mm/⅛in thick, turning regularly. (Ideally, the pizza should be around 30cm/12in long and 20cm/8in wide, but it doesn\'t matter if the shape is uneven, it just needs to fit onto the same baking tray that the vegetables were cooked on.)',5),(69,3,5,'Transfer the roasted vegetables to a bowl. Slide the pizza dough onto the baking tray and bake for 5 minutes. Take the tray out of the oven and turn the dough over.',5),(70,3,6,'For the tomato sauce, mix the passata with the oregano and spread over the dough. Top with the roasted vegetables, sprinkle with the chilli flakes and then the cheese. Bake the pizza for a further 8–10 minutes, or until the dough is cooked through and the cheese beginning to brown.',10),(71,3,7,'Season with black pepper, drizzle with a slurp of olive oil and, if you like, scatter fresh basil leaves on top just before serving.',1),(72,5,1,'Heat the oil in a non-stick frying pan over a medium heat. Add the onions and stir-fry for 15–18 minutes, or until lightly browned and crispy.',18),(73,5,2,'Put half the onions in a non-metallic mixing bowl with the yoghurt, ginger, garlic, chilli powder, cumin, cardamom, half of the salt, the lime juice, half of the chopped coriander and mint and the green chillies. Stir well to combine. Set aside the remaining coriander and mint for layering the biryani.',10),(74,5,3,'Add the lamb to the mixture and stir to coat evenly. Cover and marinade in the fridge for 6–8 hours, or overnight if possible.',480),(75,5,4,'Preheat the oven to 240C/Fan 220C/Gas 9.',10),(76,5,5,'Heat the cream and milk in a small saucepan, add the saffron, remove from the heat and leave to infuse for 30 minutes.',30),(77,5,6,'Cook the rice in a large saucepan in plenty of boiling water with the remaining salt for 6–8 minutes, or until it is just cooked, but still has a bite. Drain the rice.',8),(78,5,7,'Spread half of the lamb mixture evenly in a wide, heavy-based casserole and cover with a layer of half the rice. Sprinkle over half of the reserved onions and half of the reserved coriander and mint. Sprinkle over half of the saffron mixture. Repeat with the remaining lamb, rice, onions, herbs and saffron mixture.',15),(79,5,8,'Cover with a tight fitting lid, turn down the oven to 200C/Fan 180C/Gas 6 and cook for 1 hour. Remove and allow to stand for 15–20 minutes before serving. Garnish with pomegranate seeds if desired.',80),(80,6,1,'In a large bowl mix all the ingredients together except the rocket, then taste and adjust the seasoning, adding more salt if necessary. Toss in the rocket and serve immediately.',15),(81,7,1,'Preheat the oven to 180C/350F/Gas 4.',5),(82,7,2,'Pour the milk, cream and vanilla into a pan and boil for a minute. Remove from the heat and set aside to cool.',15),(83,7,3,'Tip the eggs and sugar into a bowl and beat together until light and fluffy. Fold the flour into the mixture, a little at a time.',10),(84,7,4,'Pour the cooled milk and cream onto the egg and sugar mixture, whisking lightly. Set aside.',2),(85,7,5,'Place a little butter into an ovenproof dish and heat in the oven until foaming. Add the plums and brown sugar and bake for 5 minutes, then pour the batter into the dish and scatter with flaked almonds, if using.',10),(86,7,6,'Cook in the oven for about 30 minutes, until golden-brown and set but still light and soft inside.',30),(87,7,7,'Dust with icing sugar and serve immediately with cream.',2),(88,8,1,'To make the biscuit base, finely crush the biscuits by putting into a sealed plastic bag and bashing with a rolling pin (alternatively, pulse to crumbs using a food processor). Transfer to a mixing bowl and add the sugar, cardamom and salt, stirring well to combine.',10),(89,8,2,'Pour the melted butter over the biscuit crumbs and mix, until thoroughly combined. Put half the crumb mixture in a 23cm/9in metal pie tin, and press evenly with your fingers. Build up the sides of the tin, compressing the base as much as possible to prevent it crumbling. Repeat with the rest of the mixture in the second tin.',10),(90,8,3,'Preheat the oven to 160C/325F/Gas 3. Put the pie bases in the freezer for 15 minutes. Remove and bake for 12 minutes, or until golden brown. Transfer to a wire rack to cool.',27),(91,8,4,'To make the filling, pour 177ml/6fl oz of cold water into a large bowl. In a separate bowl, mix the gelatine with half the sugar and sprinkle over the water. Leave to stand for a couple of minutes.',5),(92,8,5,'Meanwhile, whip the cream with the remaining sugar to form medium stiff peaks. Set aside.',5),(93,8,6,'Heat about a quarter of the mango pulp in a saucepan over a medium-low heat, until just warm. Make sure you do not boil it. Pour into the gelatine mixture and whisk, until well combined. The gelatine should dissolve completely. Gradually whisk in the remaining mango pulp.',10),(94,8,7,'Beat the cream cheese in a bowl, until soft and smooth. Add to the mango mixture with the salt. Blend the mixture using a hand blender, until completely smooth. Gently tap the bowl on the kitchen counter once or twice to pop any air bubbles.',10),(95,8,8,'Fold about a quarter of the mango mixture into the whipped cream using a spatula. Repeat with the rest of the cream, until no streaks remain.',5),(96,8,9,'Divide the filling between the cooled bases, using a rubber spatula to smooth out the filling. Refrigerate overnight, or for at least 5 hours, until firm and chilled.',300),(97,9,1,'Preheat the oven to 180C/200C Fan/Gas 4.',5),(98,9,2,'To make the chilli sauce, heat the chopped tomatoes, rose harissa, sugar and lemon juice in a small saucepan over a medium heat. Bring to a gentle boil and cook for 10 minutes, stirring regularly, until reduced to a thick sauce-like consistency. Remove from the heat and set aside to cool. You can blend the sauce until it\'s smooth using a hand-blender if you like, or just leave it chunky.',15),(99,9,3,'For the onion, mix together the onion slices, vinegar and parsley and set aside.',5),(100,9,4,'To make the yoghurt sauce, mix the yoghurt with the dried mint, season with salt and pepper and set aside.',5),(101,9,5,'Put the pittas in the oven to warm for 5 minutes.',5),(102,9,6,'To make the \'doner,\' heat a frying pan over a medium-high heat. Add the mushrooms and dry-fry for 2 minutes, stirring once or twice. Add the garlic oil, paprika, coriander, celery salt, garlic granules and black pepper and quickly coat the mushrooms. Add 2–3 tablespoons of water to the pan and stir-fry for 1 minute before removing from the heat.',5),(103,9,7,'Split the warmed pitta breads. Spoon a little white cabbage into each pitta and add a little tomato and onion. Divide the mushrooms between the pittas, add a little more cabbage and tomato, then drizzle with the chilli and yoghurt sauces. Serve immediately, topped with the pickled chillies, if using.',5);
/*!40000 ALTER TABLE `recipe_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipes`
--

DROP TABLE IF EXISTS `recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipes` (
  `recipe_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `servings` int(11) DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `tips` text DEFAULT NULL,
  `prep_time_minutes` int(11) DEFAULT NULL,
  `cook_time_minutes` int(11) DEFAULT NULL,
  PRIMARY KEY (`recipe_id`),
  KEY `fk_recipes_author` (`author_id`),
  KEY `idx_recipes_title` (`title`),
  CONSTRAINT `fk_recipes_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipes`
--

LOCK TABLES `recipes` WRITE;
/*!40000 ALTER TABLE `recipes` DISABLE KEYS */;
INSERT INTO `recipes` VALUES (1,'Spaghetti bolognese with mushrooms and sun-dried tomatoes','Branch out from the classic with this different spag bol that adds marinated mushrooms and sun-dried tomatoes. Any leftovers will taste even better the next day.',9,8,'2026-05-08 21:46:13','You can make a veggie version of this recipe by substituting soya mince or Quorn for the meat, adding it to the sauce halfway through cooking. Or simply add lots of diced vegetables to the onions, such as courgettes, carrots, peppers and aubergines.',30,30),(2,'Vegan pancakes','Try this vegan fluffy American pancake recipe for a perfect start to the day. Serve these pancakes with fresh berries, maple syrup or chocolate sauce for a really luxurious start to the day.',2,2,'2026-05-08 21:52:37','Whipped coconut cream is good with these too, but it must be well chilled before whipping. You can keep the pancakes warm in a low oven while you make the full batch.',30,30),(3,'Healthy Pizza','No yeast required for this easy, healthy pizza, topped with colourful vegetables that\'s ready in 30 minutes.This is a great recipe if you want to feed the kids in a hurry!',2,2,'2026-05-08 22:06:02','You can use any cheese you like for this pizza – it’s also a great way to use up a mix of odds and ends from the fridge.\nMake two pizzas instead of one large pizza if you like.\nAny leftover passata can be used for pasta sauces, stews or curries. It freezes well for up to 4 months. Instead of passata, you can use a bought pizza topping or strained tinned tomatoes.\nIf you don’t have self-raising wholemeal flour, use plain wholemeal flour and add 1 teaspoon of baking powder and an extra tablespoon of water if needed.',30,30),(5,'Easy lamb biryani','This lamb biryani is real centrepiece dish, but it\'s actually easy as anything to make. Serve garnished with pomegranate seeds to make it look really special.',4,8,'2026-05-08 22:09:50','Kashmiri red chilli powder is quite mild with a slightly smoky flavour that really adds to the dish.',480,120),(6,'Couscous salad','A nutritious and satisfying vegan couscous salad packed with colour, flavour and texture, from dried cranberries, pistachios and pine nuts.',5,6,'2026-05-08 22:14:12','Couscous salads are great to make ahead for easy entertaining or weekday lunches. It will keep well for a few days in the fridge.',30,10),(7,'Plum clafoutis','Halved plums are covered in a light batter and then baked in the oven to make this traditional French dessert. British plums are at their best in September, so make the most of them then and try this simple pud.',6,6,'2026-05-08 22:17:53',NULL,30,60),(8,'Mango pie','This mouthwatering mango dessert is an Indian take on a traditional Thanksgiving pie. For this recipe, you will need two 23cm/9in metal pie tins.',7,16,'2026-05-08 22:20:41','This recipe makes two pies, so halve the ingredients if you\'re not feeding a crowd.',60,60),(9,'Mushroom doner','A meat-free mushroom ‘doner’ kebab packed with two types of sauces, pickles and veg. A mighty delicious vegetarian dish.',8,4,'2026-05-08 22:26:10',NULL,30,30);
/*!40000 ALTER TABLE `recipes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `role` varchar(20) DEFAULT 'User',
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin_master','admin@recipes.local','$2y$10$vvyqrgadSX9MK5WDhrGNROkBOFPyOVnN5KGgYTZAIO9jSJdbtK.yK','System','Administrator','2026-05-08 21:45:38','Admin',NULL),(2,'jpattison','justine@example.com','$2y$10$vvyqrgadSX9MK5WDhrGNROkBOFPyOVnN5KGgYTZAIO9jSJdbtK.yK','Justine','Pattison','2026-05-08 21:52:13','User',NULL),(4,'svijayakar','sunil@example.com','$2y$10$vvyqrgadSX9MK5WDhrGNROkBOFPyOVnN5KGgYTZAIO9jSJdbtK.yK','Sunil','Vijayakar','2026-05-08 22:09:22','User',NULL),(5,'nbenkabbou','nargisse@example.com','$2y$10$vvyqrgadSX9MK5WDhrGNROkBOFPyOVnN5KGgYTZAIO9jSJdbtK.yK','Nargisse','Benkabbou','2026-05-08 22:14:00','User',NULL),(6,'jmartin','james@example.com','$2y$10$vvyqrgadSX9MK5WDhrGNROkBOFPyOVnN5KGgYTZAIO9jSJdbtK.yK','James','Martin','2026-05-08 22:17:46','User',NULL),(7,'snosrat','samin@example.com','$2y$10$vvyqrgadSX9MK5WDhrGNROkBOFPyOVnN5KGgYTZAIO9jSJdbtK.yK','Samin','Nosrat','2026-05-08 22:20:34','User',NULL),(8,'sghayour','sabrina@example.com','$2y$10$vvyqrgadSX9MK5WDhrGNROkBOFPyOVnN5KGgYTZAIO9jSJdbtK.yK','Sabrina','Ghayour','2026-05-08 22:25:52','User',NULL),(9,'jpratt','jo.pratt@example.com','$2y$10$vvyqrgadSX9MK5WDhrGNROkBOFPyOVnN5KGgYTZAIO9jSJdbtK.yK','Jo','Pratt','2026-05-08 22:29:26','User',NULL),(10,'jessica_test','jessica.test@example.com','$2y$10$vvyqrgadSX9MK5WDhrGNROkBOFPyOVnN5KGgYTZAIO9jSJdbtK.yK','Jessica','Test','2026-05-09 00:55:42','User',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'recipe_app_db'
--

--
-- Dumping routines for database 'recipe_app_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-09  1:17:17
