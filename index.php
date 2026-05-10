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
    <form action="search.php" method="GET" class="search-container">
        <input type="text" name="query" placeholder="Search by name, ingredient, cuisine...">
        <button type="submit" class="btn-search">Search</button>
    </form>
    <a href="#" class="advanced-search">Advanced Search →</a>
</section>
<main class="main-layout">
    <aside class="sidebar">
        <h3>Filters</h3>
        <ul class="filter-list">
            <li class="active">All (8)</li>
            <li>Vegetarian (6)</li>
            <li>Main (4)</li>
            <li>Vegan (3)</li>
            <li>Meat (2)</li>
            <li>Dessert (2)</li>
            <li>Healthy (2)</li>
            <li>Italian (2)</li>
        </ul>
        
        <div class="stats">
            <p>🍳 8 Recipes</p>
            <p>🌎 7 Cuisines</p>
            <p>🌱 3 Diet Types</p>
        </div>
    </aside>

    <section class="recipes-grid">
        <article class="recipe-card">
            <div class="card-badge meat">Meat</div>
            <div class="rating-tag">⭐ 4.2</div>
            <div class="card-image" style="background-color: #ddd; height: 150px;"></div> 
            <div class="card-content">
                <span class="cuisine-type">Italian</span>
                <h3>Spaghetti Bolognese</h3>
                <p>A classic Italian meat sauce, rich with beef mince, bacon, and red wine...</p>
                <div class="recipe-meta">
                    <span>🕐 110 min</span>
                    <span>👥 6 servings</span>
                    <span>🔥 624 kcal</span>
                </div>
            </div>
        </article>

        <article class="recipe-card">
            <div class="card-badge vegan">Vegan</div>
            <div class="rating-tag">⭐ 3.0</div>
            <div class="card-image" style="background-color: #eee; height: 150px;"></div>
            <div class="card-content">
                <span class="cuisine-type">American</span>
                <h3>Vegan American Pancakes</h3>
                <p>Light and fluffy American-style pancakes made without eggs or dairy...</p>
                <div class="recipe-meta">
                    <span>🕐 30 min</span>
                    <span>👥 4 servings</span>
                    <span>🔥 210 kcal</span>
                </div>
            </div>
        </article>
    </section>
</main>

</body>
</html>
