/**
 * Recipes Module
 * Handles recipe listing, searching, and detail views
 */

class RecipeManager {
    constructor() {
        this.currentPage = 1;
        this.totalPages = 1;
        this.filters = {};

        this.init();
    }

    init() {
        this.setupSearchPage();
        this.setupRecipeDetailPage();
        this.setupHomePage();
    }

    /**
     * Setup search/listing page
     */
    setupSearchPage() {
        const searchForm = document.getElementById('search-form');
        const recipeList = document.getElementById('recipe-list');
        const loadMoreBtn = document.getElementById('load-more-btn');

        if (!searchForm && !recipeList) return;

        // Load initial recipes
        this.loadRecipes();

        // Setup search form
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.currentPage = 1;
                this.filters = this.getSearchFilters(searchForm);
                this.loadRecipes();
            });

            // Auto-submit on sort change
            const sortSelect = searchForm.querySelector('[name="sort"]');
            if (sortSelect) {
                sortSelect.addEventListener('change', () => {
                    this.currentPage = 1;
                    this.filters = this.getSearchFilters(searchForm);
                    this.loadRecipes();
                });
            }
        }

        // Load more button
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => {
                this.currentPage++;
                this.loadRecipes(true);
            });
        }
    }

    /**
     * Setup recipe detail page
     */
    setupRecipeDetailPage() {
        const recipeDetail = document.getElementById('recipe-detail');
        if (!recipeDetail) return;

        const params = new URLSearchParams(window.location.search);
        const recipeId = params.get('id');

        if (!recipeId) {
            recipeDetail.innerHTML = '<p class="empty-state">Recipe not found.</p>';
            return;
        }

        this.loadRecipeDetail(recipeId);
    }

    /**
     * Setup home page featured recipes
     */
    setupHomePage() {
        const featuredGrid = document.getElementById('featured-recipes');
        if (!featuredGrid) return;

        this.loadFeaturedRecipes();
        this.loadCategories();
    }

    /**
     * Load recipes with current filters
     */
    async loadRecipes(append = false) {
        const recipeList = document.getElementById('recipe-list');
        if (!recipeList) return;

        if (!append) {
            recipeList.innerHTML = '<div class="spinner"></div><p>Loading recipes...</p>';
        }

        try {
            const params = new URLSearchParams({
                ...this.filters,
                page: this.currentPage,
                limit: 12
            });

            const queryString = params.toString();
            const endpoint = queryString ? `recipes.php?${queryString}` : 'recipes.php';

            const response = await app.apiRequest(endpoint);

            if (response.success) {
                const html = response.recipes.map(recipe => this.createRecipeCard(recipe)).join('');

                if (append) {
                    recipeList.insertAdjacentHTML('beforeend', html);
                } else {
                    recipeList.innerHTML = html;
                }

                this.totalPages = response.pagination?.total_pages || 1;
                this.updateLoadMoreButton();

            } else {
                recipeList.innerHTML = '<div class="empty-state"><p>No recipes found.</p></div>';
            }

        } catch (error) {
            recipeList.innerHTML = '<div class="alert alert-error">Failed to load recipes.</div>';
        }
    }

    /**
     * Load featured recipes for home page
     */
    async loadFeaturedRecipes() {
        const grid = document.getElementById('featured-recipes');
        if (!grid) return;

        try {
            const response = await app.apiRequest('recipes.php');

            if (response.success) {
                grid.innerHTML = response.recipes
                    .map(recipe => this.createRecipeCard(recipe))
                    .join('');
            }
        } catch (error) {
            grid.innerHTML = '<p>Failed to load featured recipes.</p>';
        }
    }

    /**
     * Load recipe detail
     */
    async loadRecipeDetail(recipeId) {
        const container = document.getElementById('recipe-detail');
        if (!container) return;

        container.innerHTML = '<div class="spinner"></div><p>Loading recipe...</p>';

        try {
            const response = await app.apiRequest(`recipe.php?id=${recipeId}`);

            if (response.success) {
                container.innerHTML = this.createRecipeDetailHTML(response.recipe);
                this.setupFavouriteButton();
                this.setupRatingForm();
            } else {
                container.innerHTML = '<div class="empty-state"><p>Recipe not found.</p></div>';
            }

        } catch (error) {
            container.innerHTML = '<div class="alert alert-error">Failed to load recipe.</div>';
        }
    }

    /**
     * Load categories
     */
    async loadCategories() {
        const container = document.getElementById('category-grid');
        if (!container) return;

        try {
            const response = await app.apiRequest('categories.php');

            if (response.success) {
                container.innerHTML = response.categories.map(cat => `
                    <a href="recipes.html?category=${cat.slug}" class="category-card">
                        <span class="category-name">${app.escapeHtml(cat.name)}</span>
                        <span class="category-count">${cat.recipe_count} recipes</span>
                    </a>
                `).join('');
            }
        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    }

    /**
     * Create recipe card HTML
     */
    createRecipeCard(recipe) {
        const avgRating = parseFloat(recipe.avg_rating) || 0;
        const stars = app.generateStars(avgRating);

        return `
            <article class="card recipe-card" data-recipe-id="${recipe.id}">
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
                        <span>👥 ${recipe.servings} servings</span>
                    </div>
                    ${avgRating > 0 ? `
                        <div class="star-rating" aria-label="Rating: ${avgRating.toFixed(1)} out of 5">
                            ${stars}
                            <span class="rating-count">(${recipe.rating_count})</span>
                        </div>
                    ` : '<p class="no-ratings">No ratings yet</p>'}
                    <p class="recipe-description">${app.escapeHtml(recipe.description?.substring(0, 120) || '')}...</p>
                    ${recipe.is_favourite ? '<span class="fav-indicator">❤️</span>' : ''}
                </div>
            </article>
        `;
    }

    /**
     * Create full recipe detail HTML
     */
    createRecipeDetailHTML(recipe) {
        const avgRating = parseFloat(recipe.avg_overall) || 0;
        const stars = app.generateStars(avgRating);
        const isFavourite = recipe.is_favourite || false;

        return `
            <article class="recipe-detail-container">
                <header class="recipe-header">
                    <h1>${app.escapeHtml(recipe.title)}</h1>
                    <div class="card-meta">
                        <span class="badge badge-${recipe.difficulty.toLowerCase()}">${recipe.difficulty}</span>
                        <span>👨‍🍳 ${app.escapeHtml(recipe.chef_name)}</span>
                        <span>📂 ${app.escapeHtml(recipe.category_names || 'Uncategorized')}</span>
                    </div>
                </header>
                
                <div class="recipe-detail">
                    <div class="recipe-main">
                        <div class="recipe-hero-image" 
                             style="background-image: url('images/${recipe.image_url || 'default-recipe.jpg'}')"
                             role="img" aria-label="${app.escapeHtml(recipe.title)}">
                        </div>
                        
                        <div class="recipe-info-grid">
                            <div class="info-card">
                                <span class="info-label">Prep Time</span>
                                <span class="info-value">${recipe.prep_time} min</span>
                            </div>
                            <div class="info-card">
                                <span class="info-label">Cook Time</span>
                                <span class="info-value">${recipe.cook_time} min</span>
                            </div>
                            <div class="info-card">
                                <span class="info-label">Total Time</span>
                                <span class="info-value">${recipe.total_time} min</span>
                            </div>
                            <div class="info-card">
                                <span class="info-label">Servings</span>
                                <span class="info-value">${recipe.servings}</span>
                            </div>
                        </div>
                        
                        <div class="ratings-summary card">
                            <h3>Ratings</h3>
                            ${avgRating > 0 ? `
                                <div class="rating-display">
                                    <span class="rating-number">${avgRating.toFixed(1)}</span>
                                    <div class="star-rating">${stars}</div>
                                    <span class="rating-count">(${recipe.rating_count} ratings)</span>
                                </div>
                            ` : '<p>No ratings yet.</p>'}
                        </div>
                        
                        <button class="btn fav-btn ${isFavourite ? 'is-favourite' : ''} btn-block"
                                data-recipe-id="${recipe.id}"
                                aria-label="${isFavourite ? 'Remove from favourites' : 'Add to favourites'}">
                            ${isFavourite ? '❤️ Saved' : '🤍 Save Recipe'}
                        </button>
                        
                        <div class="card rate-section">
                            <h3>${recipe.user_rating ? 'Update Your Rating' : 'Rate This Recipe'}</h3>
                            <form id="rating-form">
                                <input type="hidden" name="recipe_id" value="${recipe.id}">
                                
                                <div class="form-group">
                                    <label for="overall_rating" class="form-label-required">Overall Rating</label>
                                    <select id="overall_rating" name="overall_rating" class="form-select" required>
                                        <option value="">Select rating...</option>
                                        ${[5,4,3,2,1].map(n => `
                                            <option value="${n}" ${recipe.user_rating?.overall_rating == n ? 'selected' : ''}>
                                                ${'★'.repeat(n)}${'☆'.repeat(5-n)}
                                            </option>
                                        `).join('')}
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="taste_rating">Taste</label>
                                    <select id="taste_rating" name="taste_rating" class="form-select">
                                        <option value="">N/A</option>
                                        ${[1,2,3,4,5].map(n => `
                                            <option value="${n}" ${recipe.user_rating?.taste_rating == n ? 'selected' : ''}>
                                                ${n} Star${n > 1 ? 's' : ''}
                                            </option>
                                        `).join('')}
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="comment">Comment</label>
                                    <textarea id="comment" name="comment" class="form-textarea" rows="3" 
                                              maxlength="500">${app.escapeHtml(recipe.user_rating?.comment || '')}</textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Submit Rating</button>
                            </form>
                        </div>
                        
                        ${recipe.reviews?.length > 0 ? `
                            <section class="reviews-section">
                                <h3>Recent Reviews</h3>
                                ${recipe.reviews.map(review => `
                                    <div class="card review-card">
                                        <strong>${app.escapeHtml(review.username)}</strong>
                                        <div class="star-rating">${app.generateStars(review.overall_rating)}</div>
                                        <p>${app.escapeHtml(review.comment)}</p>
                                        <time>${app.formatDate(review.created_at)}</time>
                                    </div>
                                `).join('')}
                            </section>
                        ` : ''}
                    </div>
                    
                    <aside class="recipe-sidebar">
                        <section class="card">
                            <h3>Ingredients</h3>
                            <ul class="ingredients-list">
                                ${recipe.ingredients?.map(ing => `
                                    <li class="ingredient-item">
                                        <span class="ingredient-name">${app.escapeHtml(ing.name)}</span>
                                        <span class="ingredient-amount">
                                            ${ing.quantity ? ing.quantity : ''} ${app.escapeHtml(ing.unit || '')}
                                        </span>
                                    </li>
                                `).join('') || '<li>No ingredients listed.</li>'}
                            </ul>
                        </section>
                        
                        <section class="card">
                            <h3>Instructions</h3>
                            <ol class="steps-list">
                                ${recipe.steps?.map(step => `
                                    <li class="step-item">
                                        <span class="step-number">${step.step_number}</span>
                                        <div>
                                            <p>${app.escapeHtml(step.instruction)}</p>
                                            ${step.duration_minutes ? `<small>⏱️ ${step.duration_minutes} min</small>` : ''}
                                        </div>
                                    </li>
                                `).join('') || '<li>No steps listed.</li>'}
                            </ol>
                        </section>
                    </aside>
                </div>
            </article>
        `;
    }

    /**
     * Setup favourite button functionality
     */
    setupFavouriteButton() {
        const favBtn = document.querySelector('.fav-btn');
        if (!favBtn) return;

        favBtn.addEventListener('click', async () => {
            if (!app.requireAuth()) return;

            const recipeId = favBtn.dataset.recipeId;

            try {
                const response = await app.apiRequest('favourites.php', {
                    method: 'POST',
                    body: JSON.stringify({ recipe_id: parseInt(recipeId) })
                });

                if (response.success) {
                    const isFav = response.action === 'added';
                    favBtn.classList.toggle('is-favourite', isFav);
                    favBtn.innerHTML = isFav ? '❤️ Saved' : '🤍 Save Recipe';
                    favBtn.setAttribute('aria-label', isFav ? 'Remove from favourites' : 'Add to favourites');
                    app.showToast(response.message, 'success');
                }

            } catch (error) {
                app.showToast('Failed to update favourite.', 'error');
            }
        });
    }

    /**
     * Setup rating form submission
     */
    setupRatingForm() {
        const ratingForm = document.getElementById('rating-form');
        if (!ratingForm) return;

        ratingForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!app.requireAuth()) return;

            const submitBtn = ratingForm.querySelector('button[type="submit"]');
            const formData = new FormData(ratingForm);
            const data = Object.fromEntries(formData);

            // Remove empty optional fields
            Object.keys(data).forEach(key => {
                if (data[key] === '') delete data[key];
            });

            // Validate
            if (!data.overall_rating) {
                app.showToast('Please select an overall rating.', 'error');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Submitting...';

            try {
                const response = await app.apiRequest('ratings.php', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });

                if (response.success) {
                    app.showToast(response.message, 'success');
                    // Reload recipe to show updated ratings
                    setTimeout(() => location.reload(), 1000);
                } else {
                    app.showToast(response.message, 'error');
                }

            } catch (error) {
                app.showToast('Failed to submit rating.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Rating';
            }
        });
    }

    /**
     * Get search filter values from form
     */
    getSearchFilters(form) {
        const formData = new FormData(form);
        const filters = {};

        formData.forEach((value, key) => {
            if (value && value.trim()) {
                filters[key] = value.trim();
            }
        });

        return filters;
    }

    /**
     * Update load more button visibility
     */
    updateLoadMoreButton() {
        const loadMoreBtn = document.getElementById('load-more-btn');
        if (!loadMoreBtn) return;

        if (this.currentPage < this.totalPages) {
            loadMoreBtn.style.display = 'block';
        } else {
            loadMoreBtn.style.display = 'none';
        }
    }
}

// Initialize recipe manager
const recipeManager = new RecipeManager();