<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipe App - Discover Recipes</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="header">
    <h1>🍳 Discover Recipes</h1>
    <p>8 hand-picked BBC Food recipes — find your next favourite meal</p>
</header>

<section class="search-section">
    <input type="text" placeholder="Search by name, ingredient, cuisine...">
    <button class="btn-search">Search</button>
    <a href="#" class="advanced-search">Advanced Search →</a>
</section>

<main class="container">
    <aside class="sidebar">
        <h3>Filters</h3>
        <ul>
            <li>All (8)</li>
            <li>Vegetarian (6)</li>
            <li>Main (4)</li>
            <li>Vegan (3)</li>
            <li>Meat (2)</li>
            </ul>
    </aside>

    <section class="recipes-grid">
        <article class="recipe-card">
            <div class="card-tag meat">Meat</div>
            <div class="rating">⭐ 4.2</div>
            <img src="assets/bolognese.jpg" alt="Spaghetti Bolognese">
            <div class="card-content">
                <span class="cuisine">Italian</span>
                <h3>Spaghetti Bolognese</h3>
                <p>A classic Italian meat sauce, rich with beef mince...</p>
                <div class="meta-info">
                    <span>🕐 110 min</span>
                    <span>👥 6 servings</span>
                </div>
            </div>
        </article>

        <article class="recipe-card">
            <div class="card-tag vegan">Vegan</div>
            <div class="rating">⭐ 3.0</div>
            <img src="assets/pancakes.jpg" alt="Vegan Pancakes">
            <div class="card-content">
                <span class="cuisine">American</span>
                <h3>Vegan American Pancakes</h3>
                <p>Light and fluffy American-style pancakes made without eggs...</p>
                <div class="meta-info">
                    <span>🕐 30 min</span>
                    <span>👥 4 servings</span>
                </div>
            </div>
        </article>

        </section>
</main>

</body>
</html>
