-- Create Categories Table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

-- Create Recipes Table
CREATE TABLE recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    cooking_time INT, -- in minutes
    servings INT,
    calories INT,
    rating DECIMAL(3,1),
    image_url VARCHAR(255),
    category_id INT,
    cuisine VARCHAR(50),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Insert Sample Data
INSERT INTO categories (name) VALUES ('Meat'), ('Vegan'), ('Vegetarian');

INSERT INTO recipes (title, description, cooking_time, servings, calories, rating, category_id, cuisine) 
VALUES 
('Spaghetti Bolognese', 'A classic Italian meat sauce, rich with beef mince, bacon, and red wine.', 110, 6, 624, 4.2, 1, 'Italian'),
('Vegan American Pancakes', 'Light and fluffy American-style pancakes made without eggs or dairy.', 30, 4, 210, 3.0, 2, 'American'),
('Healthy Pizza', 'A lighter take on pizza with a thin wholemeal base and roasted vegetables.', 35, 2, 380, 3.3, 3, 'Italian');
