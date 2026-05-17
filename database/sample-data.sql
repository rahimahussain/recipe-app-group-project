-- =========================================================
-- SAMPLE DATA FOR ENTERPRISE RECIPE APPLICATION
-- =========================================================

USE recipe_app;

-- =========================================================
-- USERS
-- =========================================================

INSERT INTO users (
    username,
    email,
    password_hash,
    first_name,
    last_name,
    bio,
    dietary_preference,
    role,
    account_status,
    profile_image_url,
    phone_number
)
VALUES
    (
        'admin_master',
        'admin@recipeapp.com',
        '$2y$10$examplehash',
        'System',
        'Administrator',
        'Platform administrator',
        'None',
        'Admin',
        'Active',
        'profiles/admin.jpg',
        '+27110000001'
    ),
    (
        'chef_mario',
        'mario@recipeapp.com',
        '$2y$10$examplehash',
        'Mario',
        'Rossi',
        'Italian cuisine specialist',
        'None',
        'Chef',
        'Active',
        'profiles/mario.jpg',
        '+27110000002'
    ),
    (
        'vegan_queen',
        'vegan@recipeapp.com',
        '$2y$10$examplehash',
        'Sarah',
        'Green',
        'Healthy vegan food lover',
        'Vegan',
        'User',
        'Active',
        'profiles/sarah.jpg',
        '+27110000003'
    ),
    (
        'food_explorer',
        'explorer@recipeapp.com',
        '$2y$10$examplehash',
        'James',
        'Cook',
        'Exploring global recipes',
        'None',
        'User',
        'Active',
        'profiles/james.jpg',
        '+27110000004'
    );

-- =========================================================
-- CATEGORIES
-- =========================================================

INSERT INTO categories (
    name,
    slug,
    description,
    image_url
)
VALUES
    (
        'Vegan',
        'vegan',
        'Plant-based recipes',
        'categories/vegan.jpg'
    ),
    (
        'Desserts',
        'desserts',
        'Sweet treats and desserts',
        'categories/desserts.jpg'
    ),
    (
        'Healthy',
        'healthy',
        'Nutritious and balanced meals',
        'categories/healthy.jpg'
    ),
    (
        'Italian',
        'italian',
        'Italian cuisine recipes',
        'categories/italian.jpg'
    );

-- =========================================================
-- TAGS
-- =========================================================

INSERT INTO tags (
    name,
    slug
)
VALUES
    ('Quick Meals', 'quick-meals'),
    ('High Protein', 'high-protein'),
    ('Low Carb', 'low-carb'),
    ('Family Friendly', 'family-friendly');

-- =========================================================
-- RECIPES
-- =========================================================

INSERT INTO recipes (
    author_id,
    title,
    slug,
    description,
    image_url,
    prep_time_minutes,
    cook_time_minutes,
    servings,
    difficulty,
    cuisine_type,
    source_url,
    tips,
    calories,
    protein_g,
    carbs_g,
    fats_g,
    fibre_g,
    sugar_g,
    sodium_mg,
    visibility,
    moderation_status,
    average_rating,
    total_ratings,
    view_count,
    is_featured
)
VALUES
    (
        2,
        'Classic Spaghetti Bolognese',
        'classic-spaghetti-bolognese',
        'Traditional Italian spaghetti bolognese recipe.',
        'recipes/spaghetti.jpg',
        20,
        45,
        4,
        'Medium',
        'Italian',
        'https://example.com/spaghetti',
        'Use fresh basil for better flavour.',
        650,
        32.50,
        55.00,
        22.00,
        8.00,
        10.00,
        850.00,
        'Public',
        'Approved',
        4.70,
        120,
        3500,
        TRUE
    ),
    (
        3,
        'Vegan Buddha Bowl',
        'vegan-buddha-bowl',
        'Healthy vegan bowl packed with vegetables.',
        'recipes/buddha-bowl.jpg',
        15,
        10,
        2,
        'Easy',
        'Healthy',
        'https://example.com/buddha',
        'Add avocado before serving.',
        480,
        18.00,
        40.00,
        15.00,
        12.00,
        6.00,
        400.00,
        'Public',
        'Approved',
        4.90,
        80,
        2100,
        TRUE
    ),
    (
        2,
        'Chocolate Lava Cake',
        'chocolate-lava-cake',
        'Rich chocolate dessert with melted centre.',
        'recipes/lava-cake.jpg',
        15,
        12,
        2,
        'Hard',
        'Dessert',
        'https://example.com/lava-cake',
        'Serve immediately after baking.',
        720,
        9.00,
        65.00,
        42.00,
        3.00,
        48.00,
        250.00,
        'Public',
        'Approved',
        4.80,
        60,
        1800,
        FALSE
    );

-- =========================================================
-- RECIPE CATEGORIES
-- =========================================================

INSERT INTO recipe_categories (
    recipe_id,
    category_id
)
VALUES
    (1, 4),
    (2, 1),
    (2, 3),
    (3, 2);

-- =========================================================
-- INGREDIENTS
-- =========================================================

INSERT INTO ingredients (
    name,
    default_unit,
    calories_per_100g,
    allergens,
    storage_instructions
)
VALUES
    (
        'Spaghetti',
        'g',
        158.00,
        'Gluten',
        'Store in a cool dry place'
    ),
    (
        'Minced Beef',
        'g',
        250.00,
        NULL,
        'Keep refrigerated'
    ),
    (
        'Tomato Sauce',
        'ml',
        40.00,
        NULL,
        'Refrigerate after opening'
    ),
    (
        'Quinoa',
        'g',
        120.00,
        NULL,
        'Store dry'
    ),
    (
        'Avocado',
        'pcs',
        160.00,
        NULL,
        'Store at room temperature'
    ),
    (
        'Dark Chocolate',
        'g',
        540.00,
        'Milk, Soy',
        'Store cool and dry'
    );

-- =========================================================
-- RECIPE INGREDIENTS
-- =========================================================

INSERT INTO recipe_ingredients (
    recipe_id,
    ingredient_id,
    quantity,
    unit,
    order_index,
    notes
)
VALUES
    (1, 1, 500, 'g', 1, 'Cook al dente'),
    (1, 2, 400, 'g', 2, 'Lean beef preferred'),
    (1, 3, 250, 'ml', 3, NULL),
    (2, 4, 200, 'g', 1, 'Rinse before cooking'),
    (2, 5, 1, 'pcs', 2, 'Fresh avocado'),
    (3, 6, 200, 'g', 1, 'Use dark chocolate');

-- =========================================================
-- RECIPE STEPS
-- =========================================================

INSERT INTO recipe_steps (
    recipe_id,
    step_number,
    instruction,
    duration_minutes,
    image_url,
    video_url
)
VALUES
    (
        1,
        1,
        'Boil spaghetti in salted water.',
        10,
        'steps/spaghetti-step1.jpg',
        NULL
    ),
    (
        1,
        2,
        'Cook minced beef until browned.',
        15,
        'steps/spaghetti-step2.jpg',
        NULL
    ),
    (
        1,
        3,
        'Add tomato sauce and simmer.',
        20,
        'steps/spaghetti-step3.jpg',
        NULL
    ),
    (
        2,
        1,
        'Cook quinoa until fluffy.',
        15,
        NULL,
        NULL
    ),
    (
        2,
        2,
        'Arrange vegetables and avocado in bowl.',
        5,
        NULL,
        NULL
    ),
    (
        3,
        1,
        'Melt chocolate and prepare batter.',
        10,
        NULL,
        NULL
    ),
    (
        3,
        2,
        'Bake until centre remains soft.',
        12,
        NULL,
        NULL
    );

-- =========================================================
-- RECIPE MEDIA
-- =========================================================

INSERT INTO recipe_media (
    recipe_id,
    media_type,
    media_url,
    caption,
    is_primary,
    sort_order
)
VALUES
    (
        1,
        'Image',
        'media/spaghetti-main.jpg',
        'Spaghetti bolognese',
        TRUE,
        1
    ),
    (
        2,
        'Image',
        'media/buddha-main.jpg',
        'Healthy vegan bowl',
        TRUE,
        1
    ),
    (
        3,
        'Image',
        'media/lava-main.jpg',
        'Chocolate lava cake',
        TRUE,
        1
    );

-- =========================================================
-- RECIPE TAGS
-- =========================================================

INSERT INTO recipe_tags (
    recipe_id,
    tag_id
)
VALUES
    (1, 4),
    (2, 1),
    (2, 3),
    (3, 4);

-- =========================================================
-- FAVOURITES
-- =========================================================

INSERT INTO favourites (
    user_id,
    recipe_id,
    folder_name
)
VALUES
    (3, 1, 'Dinner Ideas'),
    (3, 2, 'Healthy Meals'),
    (4, 3, 'Desserts');

-- =========================================================
-- RATINGS
-- =========================================================

INSERT INTO ratings (
    user_id,
    recipe_id,
    overall_rating,
    taste_rating,
    difficulty_rating,
    aesthetics_rating,
    comment,
    is_edited,
    moderation_status
)
VALUES
    (
        3,
        1,
        5,
        5,
        3,
        4,
        'Excellent recipe. Family loved it.',
        FALSE,
        'Visible'
    ),
    (
        4,
        2,
        5,
        5,
        1,
        5,
        'Very healthy and delicious.',
        FALSE,
        'Visible'
    ),
    (
        3,
        3,
        4,
        5,
        4,
        5,
        'Amazing dessert but slightly difficult.',
        FALSE,
        'Visible'
    );

-- =========================================================
-- COMMENTS
-- =========================================================

INSERT INTO comments (
    recipe_id,
    user_id,
    parent_comment_id,
    comment_text,
    moderation_status
)
VALUES
    (
        1,
        3,
        NULL,
        'This recipe turned out fantastic!',
        'Visible'
    ),
    (
        1,
        2,
        1,
        'Glad you enjoyed it.',
        'Visible'
    ),
    (
        2,
        4,
        NULL,
        'Perfect healthy lunch option.',
        'Visible'
    );

-- =========================================================
-- BOOKMARK COLLECTIONS
-- =========================================================

INSERT INTO bookmark_collections (
    user_id,
    collection_name,
    description
)
VALUES
    (
        3,
        'Healthy Recipes',
        'Collection of healthy meals'
    ),
    (
        4,
        'Dessert Favourites',
        'Sweet recipes collection'
    );

-- =========================================================
-- COLLECTION RECIPES
-- =========================================================

INSERT INTO collection_recipes (
    collection_id,
    recipe_id
)
VALUES
    (1, 2),
    (2, 3);

-- =========================================================
-- NOTIFICATIONS
-- =========================================================

INSERT INTO notifications (
    user_id,
    notification_type,
    title,
    message,
    is_read
)
VALUES
    (
        3,
        'Recipe',
        'New Recipe Added',
        'A new vegan recipe has been published.',
        FALSE
    ),
    (
        2,
        'Comment',
        'New Comment',
        'Someone commented on your recipe.',
        TRUE
    );

-- =========================================================
-- API TOKENS
-- =========================================================

INSERT INTO api_tokens (
    user_id,
    token_hash
)
VALUES
    (
        1,
        'hashed_api_token_example_001'
    );

-- =========================================================
-- LOGIN HISTORY
-- =========================================================

INSERT INTO login_history (
    user_id,
    ip_address,
    user_agent,
    login_status
)
VALUES
    (
        1,
        '192.168.1.10',
        'Mozilla/5.0 Chrome',
        'Success'
    ),
    (
        3,
        '192.168.1.11',
        'Mozilla/5.0 Firefox',
        'Success'
    );

-- =========================================================
-- AUDIT LOGS
-- =========================================================

INSERT INTO audit_logs (
    user_id,
    entity_name,
    entity_id,
    action_type,
    old_value,
    new_value,
    ip_address,
    user_agent
)
VALUES
    (
        1,
        'Recipe',
        1,
        'CREATE',
        NULL,
        JSON_OBJECT('title', 'Classic Spaghetti Bolognese'),
        '192.168.1.10',
        'Mozilla/5.0 Chrome'
    );

-- =========================================================
-- REPORTS
-- =========================================================

INSERT INTO reports (
    reported_by_user_id,
    entity_type,
    entity_id,
    reason,
    status
)
VALUES
    (
        3,
        'Comment',
        1,
        'Spam content',
        'Open'
    );

-- =========================================================
-- END OF SAMPLE DATA
-- =========================================================