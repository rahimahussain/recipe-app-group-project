/**
 * Favourites Module
 * Manages user's saved recipes
 */

class FavouritesManager {
    constructor() {
        this.init();
    }

    init() {
        const favouritesList = document.getElementById('favourites-list');
        if (!favouritesList) return;

        if (!app.requireAuth()) return;

        this.loadFavourites();
    }

    /**
     * Load user's favourite recipes
     */
    async loadFavourites() {
        const container = document.getElementById('favourites-list');
        if (!container) return;

        container.innerHTML = '<div class="spinner"></div><p>Loading your favourites...</p>';

        try {
            const response = await app.apiRequest('favourites.php');

            if (response.success) {
                if (response.favourites.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">📝</div>
                            <h3>No favourites yet</h3>
                            <p>Start browsing recipes and save the ones you love!</p>
                            <a href="recipes.html" class="btn btn-primary">Browse Recipes</a>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = response.favourites.map(recipe => `
                    <article class="card recipe-card-horizontal" data-recipe-id="${recipe.id}">
                        <div class="card-image" style="background-image: url('images/${recipe.image_url || 'default-recipe.jpg'}')"
                             role="img" aria-label="${app.escapeHtml(recipe.title)}">
                        </div>
                        <div class="card-body">
                            <h3 class="card-title">
                                <a href="recipe-details.html?id=${recipe.id}">${app.escapeHtml(recipe.title)}</a>
                            </h3>
                            <div class="card-meta">
                                <span class="badge badge-${recipe.difficulty.toLowerCase()}">${recipe.difficulty}</span>
                                <span>⏱️ ${recipe.total_time} min</span>
                            </div>
                            <div class="star-rating">
                                ${app.generateStars(parseFloat(recipe.avg_rating) || 0)}
                            </div>
                            <button class="btn btn-outline btn-sm remove-fav-btn" data-recipe-id="${recipe.id}">
                                Remove
                            </button>
                        </div>
                    </article>
                `).join('');

                // Setup remove buttons
                this.setupRemoveButtons();
            }

        } catch (error) {
            container.innerHTML = '<div class="alert alert-error">Failed to load favourites.</div>';
        }
    }

    /**
     * Setup remove from favourites buttons
     */
    setupRemoveButtons() {
        document.querySelectorAll('.remove-fav-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const recipeId = btn.dataset.recipeId;

                try {
                    const response = await app.apiRequest('favourites.php', {
                        method: 'POST',
                        body: JSON.stringify({ recipe_id: parseInt(recipeId) })
                    });

                    if (response.success && response.action === 'removed') {
                        app.showToast('Recipe removed from favourites.', 'success');
                        // Remove card with animation
                        const card = btn.closest('.recipe-card-horizontal');
                        card.style.opacity = '0';
                        card.style.transform = 'translateX(100%)';
                        card.style.transition = 'all 0.3s ease';
                        setTimeout(() => {
                            card.remove();
                            // Reload if no more favourites
                            if (document.querySelectorAll('.recipe-card-horizontal').length === 0) {
                                this.loadFavourites();
                            }
                        }, 300);
                    }

                } catch (error) {
                    app.showToast('Failed to remove favourite.', 'error');
                }
            });
        });
    }
}

// Initialize favourites manager
const favouritesManager = new FavouritesManager();