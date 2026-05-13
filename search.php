<?php 
include('db/connection.php'); 

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
        $safe_search_query = $conn->real_escape_string($search_query);
        $sql = "SELECT * FROM recipes WHERE title LIKE '%$safe_search_query%' OR description LIKE '%$safe_search_query%'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                ?>
                <article class="recipe-card">
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p><?php echo htmlspecialchars(substr($row['description'], 0, 100)); ?>...</p>
                        <div class="recipe-meta">
                            <span>🕐 <?php echo $row['cook_time_minutes']; ?> min</span>
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
