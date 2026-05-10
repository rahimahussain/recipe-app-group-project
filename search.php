<?php 
include('db/connection.php'); 

// جلب كلمة البحث من الرابط (URL)
$search_query = isset($_GET['query']) ? $_GET['query'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results - Recipe App</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="header">
    <h1>🔍 Search Results</h1>
    <p>Showing results for: "<strong><?php echo htmlspecialchars($search_query); ?></strong>"</p>
</header>

<main class="main-layout">
    <aside class="sidebar">
        <h3>Filters</h3>
        <ul class="filter-list">
            <li class="active">All Results</li>
            <li>Vegetarian</li>
            <li>Vegan</li>
            <li>Meat</li>
        </ul>
        <a href="index.php" style="margin-top: 20px; display: block;">← Back to Home</a>
    </aside>

    <section class="recipes-grid">
        <?php
        // كود برمجي للبحث في قاعدة البيانات
        $sql = "SELECT * FROM recipes WHERE title LIKE '%$search_query%' OR cuisine LIKE '%$search_query%'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                ?>
                <article class="recipe-card">
                    <div class="card-badge"><?php echo $row['cuisine']; ?></div>
                    <div class="rating-tag">⭐ <?php echo $row['rating']; ?></div>
                    <div class="card-content">
                        <h3><?php echo $row['title']; ?></h3>
                        <p><?php echo substr($row['description'], 0, 100); ?>...</p>
                        <div class="recipe-meta">
                            <span>🕐 <?php echo $row['cooking_time']; ?> min</span>
                            <span>🔥 <?php echo $row['calories']; ?> kcal</span>
                        </div>
                    </div>
                </article>
                <?php
            }
        } else {
            echo "<p>No recipes found matching your search. Try another keyword!</p>";
        }
        ?>
    </section>
</main>

</body>
</html>
