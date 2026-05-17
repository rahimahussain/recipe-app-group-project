/**
 * Recipes Module
 * Handles recipe listing, searching, creation, and detail views
 * Updated for enterprise schema with advanced features
 */

class RecipeManager {
    constructor() {
        this.currentPage = 1;
        this.totalPages = 1;
        this.filters = {};
        this.searchTimeout = null;

        this.init();
    }

    init() {
        this.setupSearchPage();
        this.setupRecipeDetailPage();
        this.setupHomePage();
        this.setupCreateRecipeForm();
    }

    /**
     * Setup search/listing page
     */
    setupSearchPage() {
        const searchForm = document.getElementById('search-form');
        const recipeList = document.getElementById('recipe-list');
        const loadMoreBtn = document.getElementById('load-more-btn');

        if (!recipeList) return;

        // Load initial recipes
        this.loadRecipes();

        // Setup search form with debounce
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.currentPage = 1;
                this.filters = this.getSearchFilters(searchForm);
                this.loadRecipes();
            });

            // Debounced search for text input
            const searchInput = searchForm.querySelector('[name="q"]');
            if (searchInput) {
                searchInput.addEventListener('input', app.debounce(() => {
                    this.currentPage = 1;
                    this.filters = this.getSearchFilters(searchForm);
                    this.loadRecipes();
                }, 500));
            }

            // Auto-submit on select change
            searchForm.querySelectorAll('select[name]').forEach(select => {
                select.addEventListener('change', () => {
                    this.currentPage = 1;
                    this.filters = this.getSearchFilters(searchForm);
                    this.loadRecipes();
                });
            });

            // Reset button
            const resetBtn = searchForm.querySelector('[type="reset"]');
            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    setTimeout(() => {
                        this.currentPage = 1;
                        this.filters = {};
                        this.loadRecipes();
                    }, 100);
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

        // Infinite scroll
        this.setupInfiniteScroll();
    }

    /**
     * Setup recipe detail page
     */
    setupRecipeDetailPage() {
        const recipeDetail = document.getElementById('recipe-detail');
        if (!recipeDetail) return;

        const recipeId = app.getUrlParam('id');
        const recipeSlug = app.getUrlParam('slug');

        if (!recipeId && !recipeSlug) {
            app.showEmptyState('#recipe-detail', 'Recipe not found.', '🔍');
            return;
        }

        const endpoint = recipeId
            ? `recipe.php?id=${recipeId}&view=true`
            : `recipe.php?slug=${encodeURIComponent(recipeSlug)}&view=true`;

        this.loadRecipeDetail(endpoint);
    }

    /**
     * Setup home page
     */
    setupHomePage() {
        this.loadFeaturedRecipes();
        this.loadLatestRecipes();
        this.loadCategories();
        this.loadTrendingRecipes();
    }

    /**
     * Setup create recipe form
     */
    setupCreateRecipeForm() {
        const createForm = document.getElementById('create-recipe-form');
        if (!createForm) return;

        // Load categories for checkboxes
        this.loadCategoriesForForm();

        // Add ingredient row
        const addIngredientBtn = document.getElementById('add-ingredient');
        if (addIngredientBtn) {
            addIngredientBtn.addEventListener('click', () => {
                const container = document.getElementById('ingredients-container');
                const index = container.children.length;

                const row = document.createElement('div');
                row.className = 'ingredient-row';
                row.innerHTML = `
                    <input type="text" name="ingredients[${index}][name]" placeholder="Ingredient name" required>
                    <input type="number" name="ingredients[${index}][quantity]" placeholder="Qty" step="0.01">
                    <input type="text" name="ingredients[${index}][unit]" placeholder="Unit">
                    <button type="button" class="btn btn-ghost btn-sm remove-ingredient" title="Remove">×</button>
                `;
                container.appendChild(row);

                // Remove button handler
                row.querySelector('.remove-ingredient').addEventListener('click', () => row.remove());
            });
        }

        // Add step row
        const addStepBtn = document.getElementById('add-step');
        if (addStepBtn) {
            addStepBtn.addEventListener('click', () => {
                const container = document.getElementById('steps-container');
                const index = container.children.length + 1;

                const row = document.createElement('div');
                row.className = 'step-row';
                row.innerHTML = `
                    <div class="step-header">
                        <span class="step-number">Step ${index}</span>
                        <button type="button" class="btn btn-ghost btn-sm remove-step" title="Remove">×</button>
                    </div>
                    <textarea name="steps[${index - 1}][instruction]" placeholder="Step instructions..." required></textarea>
                    <input type="number" name="steps[${index - 1}][duration_minutes]" placeholder="Duration (minutes)" min="0">
                `;
                container.appendChild(row);

                row.querySelector('.remove-step').addEventListener('click', () => {
                    row.remove();
                    this.renumberSteps(container);
                });
            });
        }

        // Form submission
        createForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!app.requireAuth()) return;

            const submitBtn = createForm.querySelector('button[type="submit"]');
            authManager.setLoading(submitBtn, true);

            // Collect form data
            const formData = new FormData(createForm);
            const data = {
                title: formData.get('title'),
                description: formData.get('description'),
                prep_time_minutes: parseInt(formData.get('prep_time_minutes')) || 0,
                cook_time_minutes: parseInt(formData.get('cook_time_minutes')) || 0,
                servings: parseInt(formData.get('servings')) || 4,
                difficulty: formData.get('difficulty') || 'Medium',
                cuisine_type: formData.get('cuisine_type'),
                tips: formData.get('tips'),
                visibility: formData.get('visibility') || 'Public',
                categories: formData.getAll('categories[]').map(Number),
                ingredients: [],
                steps: []
            };

            // Collect ingredients
            const ingredientRows = document.querySelectorAll('.ingredient-row');
            ingredientRows.forEach(row => {
                const name = row.querySelector('[name*="[name]"]')?.value;
                if (name) {
                    data.ingredients.push({
                        name: name,
                        quantity: parseFloat(row.querySelector('[name*="[quantity]"]')?.value) || null,
                        unit: row.querySelector('[name*="[unit]"]')?.value || null
                    });
                }
            });

            // Collect steps
            const stepRows = document.querySelectorAll('.step-row');
            stepRows.forEach((row, index) => {
                const instruction = row.querySelector('textarea')?.value;
                if (instruction) {
                    data.steps.push({
                        step_number: index + 1,
                        instruction: instruction,
                        duration_minutes: parseInt(row.querySelector('[name*="[duration_minutes]"]')?.value) || null
                    });
                }
            });

            // Generate slug
            data.slug = data.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

            try {
                const response = await app.apiRequest('recipes.php', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });

                if (response.success) {
                    app.showToast('Recipe created successfully!', 'success');
                    window.location.href = `recipe-details.html?id=${response.recipe_id}`;
                } else {
                    app.showToast(response.message || 'Failed to create recipe.', 'error');
                }

            } catch (error) {
                app.showToast(error.message || 'Failed to create recipe.', 'error');
            } finally {
                authManager.setLoading(submitBtn, false);
            }
        });
    }

    /**
     * Load recipes with current filters
     */
    async loadRecipes(append = false) {
        const recipeList = document.getElementById('recipe-list');
        if (!recipeList) return;

        if (!append) {
            app.showLoading('#recipe-list', 'Loading recipes...');
        }

        try {
            const params = new URLSearchParams({
                ...this.filters,
                page: this.currentPage,
                limit: 12
            });

            const response = await app.apiRequest(`recipes.php?${params.toString()}`);

            if (response.success) {
                const html = response.recipes.map(recipe => this.createRecipeCard(recipe)).join('');

                if (append) {
                    recipeList.insertAdjacentHTML('beforeend', html);
                } else {
                    recipeList.innerHTML = html;
                }

                this.totalPages = response.pagination?.total_pages || 1;
                this.updateLoadMoreButton(response.pagination);
                this.updateResultCount(response.pagination);

                if (response.recipes.length === 0 && !append) {
                    app.showEmptyState('#recipe-list', 'No recipes found matching your criteria.', '🔍');
                }
            }

        } catch (error) {
            if (!append) {
                recipeList.innerHTML = '<div class="alert alert-error">Failed to load recipes. Please try again.</div>';
            }
        }
    }

    /**
     * Load recipe detail
     */
    async loadRecipeDetail(endpoint) {
        const container = document.getElementById('recipe-detail');
        if (!container) return;

        app.showLoading('#recipe-detail', 'Loading recipe...');

        try {
            const response = await app.apiRequest(endpoint);

            if (response.success && response.recipe) {
                container.innerHTML = this.createRecipeDetailHTML(response.recipe);

                // Setup interactive elements
                this.setupFavouriteButton();
                this.setupRatingForm();
                this.setupPrintButton();
                this.setupServingsAdjuster(response.recipe.servings);
                this.loadRecipeRatings(response.recipe.id);

                // Scroll to top
                window.scrollTo(0, 0);
            } else {
                container.innerHTML = '<div class="empty-state"><p>Recipe not found.</p></div>';
            }

        } catch (error) {
            container.innerHTML = `
                <div class="alert alert-error">
                    <p>Failed to load recipe.</p>
                    <button onclick="location.reload()" class="btn btn-secondary">Try Again</button>
                </div>
            `;
        }
    }

    /**
     * Load ratings for recipe
     */
    async loadRecipeRatings(recipeId) {
        const container = document.getElementById('reviews-container');
        if (!container) return;

        try {
            const response = await app.apiRequest(`ratings.php?recipe_id=${recipeId}&limit=5`);

            if (response.success && response.ratings.length > 0) {
                container.innerHTML = response.ratings.map(rating => `
                    <div class="review-card card">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <strong>${app.escapeHtml(rating.user_full_name || rating.username)}</strong>
                                <span class="badge badge-${rating.user_role?.toLowerCase() || 'user'}">${rating.user_role || 'User'}</span>
                            </div>
                            <div class="review-meta">
                                ${app.generateStars(rating.overall_rating)}
                                <time datetime="${rating.created_at}">${app.formatDate(rating.created_at)}</time>
                            </div>
                        </div>
                        ${rating.comment ? `<p class="review-comment">${app.escapeHtml(rating.comment)}</p>` : ''}
                        ${rating.is_edited ? '<small class="edited-badge">(edited)</small>' : ''}
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p>No reviews yet. Be the first to rate!</p>';
            }

        } catch (error) {
            console.error('Failed to load ratings:', error);
        }
    }

    /**
     * Load featured recipes
     */
    async loadFeaturedRecipes() {
        const grid = document.getElementById('featured-recipes');
        if (!grid) return;

        try {
            const response = await app.apiRequest('recipes.php?featured=true&limit=6');

            if (response.success) {
                grid.innerHTML = response.recipes
                    .map(recipe => this.createRecipeCard(recipe))
                    .join('');
            }
        } catch (error) {
            console.error('Failed to load featured recipes:', error);
        }
    }

    /**
     * Load latest recipes
     */
    async loadLatestRecipes() {
        const grid = document.getElementById('latest-recipes');
        if (!grid) return;

        try {
            const response = await app.apiRequest('recipes.php?latest=true&limit=6');

            if (response.success) {
                grid.innerHTML = response.recipes
                    .map(recipe => this.createRecipeCard(recipe, true))
                    .join('');
            }
        } catch (error) {
            console.error('Failed to load latest recipes:', error);
        }
    }

    /**
     * Load trending recipes
     */
    async loadTrendingRecipes() {
        const grid = document.getElementById('trending-recipes');
        if (!grid) return;

        try {
            const response = await app.apiRequest('recipes.php?trending=true&limit=5');

            if (response.success) {
                grid.innerHTML = response.recipes
                    .map((recipe, index) => `
                        <div class="trending-item">
                            <span class="trending-rank">#${index + 1}</span>
                            <a href="recipe-details.html?id=${recipe.id}">${app.escapeHtml(recipe.title)}</a>
                            <span class="trending-views">👁️ ${recipe.view_count}</span>
                        </div>
                    `)
                    .join('');
            }
        } catch (error) {
            console.error('Failed to load trending:', error);
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
                    <a href="recipes.html?category_slug=${cat.slug}" class="category-card">
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
     * Load categories for recipe creation form
     */
    async loadCategoriesForForm() {
        const container = document.getElementById('categories-checkboxes');
        if (!container) return;

        try {
            const response = await app.apiRequest('categories.php');

            if (response.success) {
                container.innerHTML = response.categories.map(cat => `
                    <label class="checkbox-label">
                        <input type="checkbox" name="categories[]" value="${cat.id}">
                        ${app.escapeHtml(cat.name)}
                    </label>
                `).join('');
            }
        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    }

    /**
     * Create recipe card HTML
     */
    createRecipeCard(recipe, compact = false) {
        const avgRating = parseFloat(recipe.average_rating) || 0;

        return `
            <article class="card recipe-card ${compact ? 'recipe-card-compact' : ''}" data-recipe-id="${recipe.id}">
                <a href="recipe-details.html?id=${recipe.id}" class="card-image-link">
                    <div class="card-image" 
                         style="background-image: url('images/${recipe.image_url || 'default-recipe.jpg'}')"
                         role="img" 
                         aria-label="${app.escapeHtml(recipe.title)}">
                        ${recipe.is_featured ? '<span class="featured-badge">⭐ Featured</span>' : ''}
                    </div>
                </a>
                <div class="card-body">
                    <h3 class="card-title">
                        <a href="recipe-details.html?id=${recipe.id}">${app.escapeHtml(recipe.title)}</a>
                    </h3>
                    
                    ${!compact ? `
                        <p class="recipe-author">by ${app.escapeHtml(recipe.author_full_name || recipe.author_username)}</p>
                    ` : ''}
                    
                    <div class="card-meta">
                        ${app.generateDifficultyBadge(recipe.difficulty)}
                        <span class="meta-item">⏱️ ${recipe.total_time_minutes} min</span>
                        <span class="meta-item">👥 ${recipe.servings} servings</span>
                    </div>
                    
                    ${avgRating > 0 ? `
                        <div class="star-rating-container">
                            ${app.generateStars(avgRating)}
                            <span class="rating-count">(${recipe.total_ratings})</span>
                        </div>
                    ` : `<p class="no-ratings">No ratings yet</p>`}
                    
                    ${!compact && recipe.description ? `
                        <p class="recipe-description">${app.truncate(recipe.description, 120)}</p>
                    ` : ''}
                    
                    <div class="card-footer">
                        <a href="recipe-details.html?id=${recipe.id}" class="btn btn-sm btn-primary">View Recipe</a>
                        ${recipe.is_favourite ? '<span class="fav-indicator" title="In your favourites">❤️</span>' : ''}
                    </div>
                </div>
            </article>
        `;
    }

    /**
     * Create full recipe detail HTML
     */
    createRecipeDetailHTML(recipe) {
        const avgRating = parseFloat(recipe.average_rating) || 0;
        const ratingSummary = recipe.rating_summary || {};
        const isFavourite = recipe.is_favourite || false;

        return `
            <article class="recipe-detail-container">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="index.html">Home</a> &raquo;
                    <a href="recipes.html">Recipes</a> &raquo;
                    <span aria-current="page">${app.escapeHtml(recipe.title)}</span>
                </nav>
                
                <header class="recipe-header">
                    <h1>${app.escapeHtml(recipe.title)}</h1>
                    <div class="recipe-meta-header">
                        <span>by <a href="#">${app.escapeHtml(recipe.author_full_name)}</a></span>
                        ${recipe.author_role === 'Chef' ? '<span class="badge badge-chef">Verified Chef</span>' : ''}
                        <time datetime="${recipe.created_at}">${app.formatDate(recipe.created_at)}</time>
                        ${recipe.is_featured ? '<span class="badge badge-featured">Featured</span>' : ''}
                    </div>
                </header>
                
                <div class="recipe-detail">
                    <div class="recipe-main">
                        <div class="recipe-hero-image" 
                             style="background-image: url('images/${recipe.image_url || 'default-recipe.jpg'}')"
                             role="img" 
                             aria-label="${app.escapeHtml(recipe.title)}">
                        </div>
                        
                        ${recipe.media && recipe.media.length > 0 ? `
                            <div class="recipe-gallery">
                                ${recipe.media.map(m => `
                                    <img src="${app.escapeHtml(m.media_url)}" alt="${app.escapeHtml(m.caption || '')}" class="gallery-image">
                                `).join('')}
                            </div>
                        ` : ''}
                        
                        <div class="recipe-quick-info">
                            <div class="info-card">
                                <span class="info-label">Prep Time</span>
                                <span class="info-value">${recipe.prep_time_minutes} min</span>
                            </div>
                            <div class="info-card">
                                <span class="info-label">Cook Time</span>
                                <span class="info-value">${recipe.cook_time_minutes} min</span>
                            </div>
                            <div class="info-card">
                                <span class="info-label">Total Time</span>
                                <span class="info-value">${recipe.total_time_minutes} min</span>
                            </div>
                            <div class="info-card">
                                <span class="info-label">Servings</span>
                                <span class="info-value" id="servings-display">${recipe.servings}</span>
                                <div class="servings-adjust">
                                    <button class="btn btn-sm" id="decrease-servings" aria-label="Decrease servings">−</button>
                                    <button class="btn btn-sm" id="increase-servings" aria-label="Increase servings">+</button>
                                </div>
                            </div>
                        </div>
                        
                        ${recipe.difficulty ? `
                            <div class="recipe-difficulty">
                                Difficulty: ${app.generateDifficultyBadge(recipe.difficulty)}
                            </div>
                        ` : ''}
                        
                        <div class="ratings-summary card">
                            <h3>Ratings</h3>
                            ${avgRating > 0 ? `
                                <div class="rating-display">
                                    <span class="rating-number">${avgRating.toFixed(1)}</span>
                                    ${app.generateStars(avgRating)}
                                    <span class="rating-count">(${ratingSummary.total_ratings || 0} ratings)</span>
                                </div>
                                <div class="rating-breakdown">
                                    <div class="rating-bar">
                                        <span>5 ★</span>
                                        <div class="bar"><div class="bar-fill" style="width: ${((ratingSummary.five_star || 0) / (ratingSummary.total_ratings || 1)) * 100}%"></div></div>
                                    </div>
                                    <div class="rating-bar">
                                        <span>4 ★</span>
                                        <div class="bar"><div class="bar-fill" style="width: ${((ratingSummary.four_star || 0) / (ratingSummary.total_ratings || 1)) * 100}%"></div></div>
                                    </div>
                                    <div class="rating-bar">
                                        <span>3 ★</span>
                                        <div class="bar"><div class="bar-fill" style="width: ${((ratingSummary.three_star || 0) / (ratingSummary.total_ratings || 1)) * 100}%"></div></div>
                                    </div>
                                    <div class="rating-bar">
                                        <span>2 ★</span>
                                        <div class="bar"><div class="bar-fill" style="width: ${((ratingSummary.two_star || 0) / (ratingSummary.total_ratings || 1)) * 100}%"></div></div>
                                    </div>
                                    <div class="rating-bar">
                                        <span>1 ★</span>
                                        <div class="bar"><div class="bar-fill" style="width: ${((ratingSummary.one_star || 0) / (ratingSummary.total_ratings || 1)) * 100}%"></div></div>
                                    </div>
                                </div>
                            ` : '<p>No ratings yet.</p>'}
                        </div>
                        
                        <div class="recipe-actions">
                            <button class="btn fav-btn ${isFavourite ? 'is-favourite' : ''} btn-lg"
                                    data-recipe-id="${recipe.id}"
                                    aria-label="${isFavourite ? 'Remove from favourites' : 'Add to favourites'}">
                                ${isFavourite ? '❤️ Saved' : '🤍 Save Recipe'}
                            </button>
                            <button class="btn btn-outline print-btn" onclick="window.print()" aria-label="Print recipe">
                                🖨️ Print
                            </button>
                            <button class="btn btn-outline share-btn" onclick="navigator.share && navigator.share({title: '${app.escapeHtml(recipe.title)}', url: window.location.href})" aria-label="Share recipe">
                                📤 Share
                            </button>
                        </div>
                        
                        ${recipe.description ? `
                            <div class="recipe-description-section card">
                                <h3>Description</h3>
                                <p>${app.escapeHtml(recipe.description)}</p>
                            </div>
                        ` : ''}
                        
                        ${recipe.tips ? `
                            <div class="recipe-tips card">
                                <h3>💡 Tips</h3>
                                <p>${app.escapeHtml(recipe.tips)}</p>
                            </div>
                        ` : ''}
                        
                        ${recipe.calories || recipe.protein_g ? `
                            <div class="nutrition-info card">
                                <h3>Nutritional Information</h3>
                                <div class="nutrition-grid">
                                    ${recipe.calories ? `<div class="nutrition-item"><span>Calories</span><strong>${recipe.calories}</strong></div>` : ''}
                                    ${recipe.protein_g ? `<div class="nutrition-item"><span>Protein</span><strong>${recipe.protein_g}g</strong></div>` : ''}
                                    ${recipe.carbs_g ? `<div class="nutrition-item"><span>Carbs</span><strong>${recipe.carbs_g}g</strong></div>` : ''}
                                    ${recipe.fats_g ? `<div class="nutrition-item"><span>Fats</span><strong>${recipe.fats_g}g</strong></div>` : ''}
                                    ${recipe.fibre_g ? `<div class="nutrition-item"><span>Fibre</span><strong>${recipe.fibre_g}g</strong></div>` : ''}
                                    ${recipe.sugar_g ? `<div class="nutrition-item"><span>Sugar</span><strong>${recipe.sugar_g}g</strong></div>` : ''}
                                    ${recipe.sodium_mg ? `<div class="nutrition-item"><span>Sodium</span><strong>${recipe.sodium_mg}mg</strong></div>` : ''}
                                </div>
                            </div>
                        ` : ''}
                        
                        <div class="card rate-section">
                            <h3>Rate This Recipe</h3>
                            <form id="rating-form">
                                <input type="hidden" name="recipe_id" value="${recipe.id}">
                                <div class="star-rating-input">
                                    <label>Overall Rating *</label>
                                    <div class="stars-input" data-rating="0">
                                        ${[5,4,3,2,1].map(n => `
                                            <input type="radio" id="star${n}" name="overall_rating" value="${n}" 
                                                   ${recipe.user_rating?.overall_rating == n ? 'checked' : ''}>
                                            <label for="star${n}" title="${n} stars">★</label>
                                        `).join('')}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="comment">Comment</label>
                                    <textarea id="comment" name="comment" class="form-textarea" rows="3" maxlength="500"
                                              placeholder="Share your thoughts...">${app.escapeHtml(recipe.user_rating?.comment || '')}</textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">${recipe.user_rating ? 'Update Rating' : 'Submit Rating'}</button>
                            </form>
                        </div>
                        
                        <section class="reviews-section">
                            <h3>Recent Reviews</h3>
                            <div id="reviews-container">
                                <!-- Populated by JS -->
                            </div>
                        </section>
                        
                        ${recipe.related_recipes && recipe.related_recipes.length > 0 ? `
                            <section class="related-recipes">
                                <h3>Related Recipes</h3>
                                <div class="recipe-grid">
                                    ${recipe.related_recipes.map(r => this.createRecipeCard(r, true)).join('')}
                                </div>
                            </section>
                        ` : ''}
                    </div>
                    
                    <aside class="recipe-sidebar">
                        <section class="card">
                            <h3>Ingredients</h3>
                            <ul class="ingredients-list">
                                ${recipe.ingredients?.map(ing => `
                                    <li class="ingredient-item">
                                        <span class="ingredient-name">${app.escapeHtml(ing.ingredient_name || ing.name)}</span>
                                        <span class="ingredient-amount">
                                            ${ing.quantity ? ing.quantity : ''} ${app.escapeHtml(ing.unit || '')}
                                        </span>
                                        ${ing.notes ? `<small class="ingredient-notes">${app.escapeHtml(ing.notes)}</small>` : ''}
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
                                        <div class="step-content">
                                            <p>${app.escapeHtml(step.instruction)}</p>
                                            ${step.duration_minutes ? `<small>⏱️ ${step.duration_minutes} min</small>` : ''}
                                        </div>
                                    </li>
                                `).join('') || '<li>No steps listed.</li>'}
                            </ol>
                        </section>
                        
                        ${recipe.category_names ? `
                            <section class="card">
                                <h3>Categories</h3>
                                <div class="tags-container">
                                    ${recipe.category_names.split(',').map(cat => `
                                        <a href="recipes.html?category_slug=${app.escapeHtml(recipe.category_slugs?.split(',')[recipe.category_names.split(',').indexOf(cat)]?.trim() || '')}" 
                                           class="tag">${app.escapeHtml(cat.trim())}</a>
                                    `).join('')}
                                </div>
                            </section>
                        ` : ''}
                        
                        ${recipe.cuisine_type ? `
                            <section class="card">
                                <h3>Cuisine</h3>
                                <p>${app.escapeHtml(recipe.cuisine_type)}</p>
                            </section>
                        ` : ''}
                        
                        ${recipe.source_url ? `
                            <section class="card">
                                <h3>Source</h3>
                                <a href="${app.escapeHtml(recipe.source_url)}" target="_blank" rel="noopener">View Original Recipe →</a>
                            </section>
                        ` : ''}
                    </aside>
                </div>
            </article>
        `;
    }

    /**
     * Setup favourite button
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
     * Setup rating form
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

            // Validate
            if (!data.overall_rating) {
                app.showToast('Please select an overall rating.', 'warning');
                return;
            }

            authManager.setLoading(submitBtn, true);

            try {
                const response = await app.apiRequest('ratings.php', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });

                if (response.success) {
                    app.showToast(response.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    app.showToast(response.message || 'Failed to submit rating.', 'error');
                }

            } catch (error) {
                app.showToast('Failed to submit rating.', 'error');
            } finally {
                authManager.setLoading(submitBtn, false);
            }
        });
    }

    /**
     * Setup print button
     */
    setupPrintButton() {
        const printBtn = document.querySelector('.print-btn');
        if (printBtn) {
            printBtn.addEventListener('click', () => window.print());
        }
    }

    /**
     * Setup servings adjuster
     */
    setupServingsAdjuster(originalServings) {
        const decreaseBtn = document.getElementById('decrease-servings');
        const increaseBtn = document.getElementById('increase-servings');
        const display = document.getElementById('servings-display');

        if (!decreaseBtn || !increaseBtn || !display) return;

        let currentServings = originalServings;

        const updateServings = (newServings) => {
            if (newServings < 1) return;
            currentServings = newServings;
            display.textContent = currentServings;

            // Adjust ingredient quantities
            const ratio = currentServings / originalServings;
            document.querySelectorAll('.ingredient-amount').forEach(amount => {
                const originalQty = parseFloat(amount.dataset.originalQuantity || amount.textContent);
                if (!isNaN(originalQty)) {
                    amount.textContent = (originalQty * ratio).toFixed(1);
                }
            });
        };

        decreaseBtn.addEventListener('click', () => updateServings(currentServings - 1));
        increaseBtn.addEventListener('click', () => updateServings(currentServings + 1));
    }

    /**
     * Get search filter values
     */
    getSearchFilters(form) {
        const formData = new FormData(form);
        const filters = {};

        formData.forEach((value, key) => {
            if (value && value.trim() !== '') {
                filters[key] = value.trim();
            }
        });

        return filters;
    }

    /**
     * Update load more button
     */
    updateLoadMoreButton(pagination) {
        const loadMoreBtn = document.getElementById('load-more-btn');
        if (!loadMoreBtn) return;

        if (pagination && pagination.has_more) {
            loadMoreBtn.style.display = 'block';
            loadMoreBtn.textContent = `Load More (${pagination.current_page} of ${pagination.total_pages})`;
        } else {
            loadMoreBtn.style.display = 'none';
        }
    }

    /**
     * Update result count
     */
    updateResultCount(pagination) {
        const countEl = document.getElementById('result-count');
        if (!countEl || !pagination) return;

        countEl.textContent = `${pagination.total_recipes} recipe${pagination.total_recipes !== 1 ? 's' : ''} found`;
    }

    /**
     * Setup infinite scroll
     */
    setupInfiniteScroll() {
        const loadMoreBtn = document.getElementById('load-more-btn');
        if (!loadMoreBtn) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && this.currentPage < this.totalPages) {
                    this.currentPage++;
                    this.loadRecipes(true);
                }
            });
        }, { threshold: 0.1 });

        observer.observe(loadMoreBtn);
    }

    /**
     * Renumber steps after removal
     */
    renumberSteps(container) {
        const steps = container.querySelectorAll('.step-row');
        steps.forEach((step, index) => {
            const numberEl = step.querySelector('.step-number');
            if (numberEl) numberEl.textContent = `Step ${index + 1}`;

            const textarea = step.querySelector('textarea');
            if (textarea) {
                textarea.name = `steps[${index}][instruction]`;
            }

            const durationInput = step.querySelector('input[type="number"]');
            if (durationInput) {
                durationInput.name = `steps[${index}][duration_minutes]`;
            }
        });
    }
}

// Initialize recipe manager
const recipeManager = new RecipeManager();