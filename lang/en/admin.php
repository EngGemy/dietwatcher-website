<?php

return [
    // Navigation Groups
    'navigation_groups' => [
        'content' => 'Content',
        'blog' => 'Blog',
        'meal_management' => 'Meal Management',
        'meal_plans' => 'Meal Plans',
        'users_permissions' => 'Users & Permissions',
        'faq' => 'FAQ',
        'testimonials' => 'Testimonials',
        'settings' => 'Settings',
    ],

    // Menu Items
    'menu_items' => [
        'navigation_label' => 'Menu Items',
        'model_label' => 'Menu Item',
        'plural_model_label' => 'Menu Items',
        'title' => 'Website Menu',
        'pages' => [
            'create' => 'Add Menu Item',
            'edit' => 'Edit Menu Item',
            'list' => 'Menu Items',
        ],
        'fields' => [
            'label' => 'Label',
            'label_placeholder' => 'e.g. About, Contact',
            'url' => 'URL',
            'url_placeholder' => '/about or https://...',
            'route_name' => 'Route name',
            'route_name_placeholder' => 'optional',
            'target' => 'Open in',
            'target_self' => 'Same window',
            'target_blank' => 'New tab',
            'order' => 'Order',
            'is_active' => 'Active',
            'parent' => 'Parent Item',
        ],
        'empty_state' => [
            'heading' => 'No menu items yet',
            'description' => 'Add your first menu item.',
        ],
    ],

    // Hero Sections
    'hero_sections' => [
        'navigation_label' => 'Hero Sections',
        'model_label' => 'Hero Section',
        'plural_model_label' => 'Hero Sections',
        'title' => 'Hero Sections',
        'pages' => [
            'create' => 'Add Hero Section',
            'edit' => 'Edit Hero Section',
            'list' => 'Hero Sections',
        ],
        'fields' => [
            'title' => 'Headline',
            'title_placeholder' => 'Healthy Meals Delivered Daily...',
            'subtitle' => 'Subtitle',
            'subtitle_placeholder' => 'Short description',
            'cta_text' => 'Primary button text',
            'cta_text_placeholder' => 'App Store',
            'cta_secondary_text' => 'Secondary button text',
            'cta_secondary_text_placeholder' => 'Google Play',
            'image_desktop' => 'Desktop image',
            'image_mobile' => 'Mobile mockup image',
            'app_store_url' => 'App Store URL',
            'play_store_url' => 'Google Play URL',
            'order' => 'Order',
            'is_active' => 'Active',
        ],
        'empty_state' => [
            'heading' => 'No hero sections yet',
            'description' => 'Create your first hero section.',
        ],
    ],

    // Features
    'features' => [
        'navigation_label' => 'Features',
        'model_label' => 'Feature',
        'plural_model_label' => 'Features',
        'title' => 'Features',
        'pages' => [
            'create' => 'Add Feature',
            'edit' => 'Edit Feature',
            'list' => 'Features',
        ],
        'fields' => [
            'title' => 'Title',
            'title_placeholder' => 'e.g. Order Your Meal',
            'description' => 'Description',
            'description_placeholder' => 'Short description',
            'image' => 'Image',
            'icon' => 'Icon',
            'order' => 'Order',
            'is_active' => 'Active',
        ],
        'empty_state' => [
            'heading' => 'No features yet',
            'description' => 'Add your first feature.',
        ],
    ],

    // Settings
    'settings' => [
        'navigation_label' => 'Settings',
        'title' => 'Settings',
        'groups' => [
            'general' => 'General',
            'header' => 'Header',
            'footer' => 'Footer',
            'social' => 'Social Links',
        ],
        'fields' => [
            'site_name' => 'Site name',
            'logo_header' => 'Header logo',
            'logo_footer' => 'Footer logo',
            'favicon' => 'Favicon',
            'contact_email' => 'Contact email',
            'copyright' => 'Copyright text',
            'footer_description' => 'Footer Description',
            'app_links' => 'App Links',
        ],
        'messages' => [
            'saved' => 'Settings saved successfully.',
        ],
    ],

    // Roles
    'roles' => [
        'navigation_label' => 'Roles',
        'model_label' => 'Role',
        'plural_model_label' => 'Roles',
        'title' => 'Roles',
        'pages' => [
            'create' => 'Create Role',
            'edit' => 'Edit Role',
            'list' => 'Roles',
        ],
        'fields' => [
            'name' => 'Name',
            'name_placeholder' => 'e.g. Admin, Editor',
            'guard_name' => 'Guard',
            'permissions' => 'Permissions',
        ],
        'empty_state' => [
            'heading' => 'No roles yet',
            'description' => 'Create your first role.',
        ],
    ],

    // Permissions
    'permissions' => [
        'navigation_label' => 'Permissions',
        'model_label' => 'Permission',
        'plural_model_label' => 'Permissions',
        'title' => 'Permissions',
        'pages' => [
            'create' => 'Create Permission',
            'edit' => 'Edit Permission',
            'list' => 'Permissions',
        ],
        'fields' => [
            'name' => 'Name',
            'name_placeholder' => 'e.g. create-users',
            'guard_name' => 'Guard',
        ],
        'empty_state' => [
            'heading' => 'No permissions yet',
            'description' => 'Create your first permission.',
        ],
    ],

    // Users
    'users' => [
        'navigation_label' => 'Users',
        'model_label' => 'User',
        'plural_model_label' => 'Users',
        'title' => 'Users',
        'pages' => [
            'create' => 'Create User',
            'edit' => 'Edit User',
            'view' => 'View User',
            'list' => 'Users',
        ],
        'fields' => [
            'name' => 'Name',
            'name_placeholder' => 'Enter full name',
            'name_help' => 'Required. Min 2 characters.',
            'email' => 'Email',
            'email_placeholder' => 'Enter email address',
            'password' => 'Password',
            'password_placeholder' => 'Enter password',
            'roles' => 'Roles',
            'is_active' => 'Active',
            'email_verified_at' => 'Email Verified',
        ],
        'actions' => [
            'create' => 'Create User',
            'approve' => 'Approve',
            'delete' => 'Delete',
        ],
        'messages' => [
            'created' => 'User created successfully.',
            'updated' => 'User updated successfully.',
            'deleted' => 'User deleted.',
        ],
        'filters' => [
            'is_active' => 'Active',
        ],
        'empty_state' => [
            'heading' => 'No users yet',
            'description' => 'Create your first user to get started.',
        ],
    ],

    // Plans
    'plans' => [
        'navigation_label' => 'Plans',
        'model_label' => 'Plan',
        'plural_model_label' => 'Plans',
        'title' => 'Meal Plans',
        'pages' => [
            'create' => 'Create Plan',
            'edit' => 'Edit Plan',
            'list' => 'Plans',
        ],
        'fields' => [
            'name' => 'Name',
            'name_placeholder' => 'e.g. High Protein Plan',
            'subtitle' => 'Subtitle',
            'subtitle_placeholder' => 'Short tagline',
            'description' => 'Description',
            'description_placeholder' => 'Full plan description',
            'ingredients' => 'Ingredients',
            'ingredients_placeholder' => 'List of ingredients',
            'benefits' => 'Benefits',
            'benefits_placeholder' => 'Plan benefits',
            'hero_image' => 'Hero Image',
            'is_active' => 'Active',
            'show_in_app' => 'Show in App',
            'order_column' => 'Order',
        ],
        'empty_state' => [
            'heading' => 'No plans yet',
            'description' => 'Create your first meal plan.',
        ],
        'tabs' => [
            'images' => 'Gallery Images',
            'calories' => 'Calorie Options',
            'durations' => 'Duration & Pricing',
            'menus' => 'Menus',
            'categories' => 'Categories',
            'meal_types' => 'Meal Types',
        ],
    ],

    // Plan Categories
    'plan_categories' => [
        'navigation_label' => 'Plan Categories',
        'model_label' => 'Category',
        'plural_model_label' => 'Categories',
        'title' => 'Plan Categories',
        'pages' => [
            'create' => 'Create Category',
            'edit' => 'Edit Category',
            'list' => 'Categories',
        ],
        'fields' => [
            'name' => 'Name',
            'name_placeholder' => 'e.g. High Protein',
            'slug' => 'Slug',
            'slug_help' => 'Used in URLs (e.g. high-protein)',
            'is_active' => 'Active',
            'order_column' => 'Order',
        ],
        'empty_state' => [
            'heading' => 'No categories yet',
            'description' => 'Create your first category.',
        ],
    ],

    // Meal Types
    'meal_types' => [
        'navigation_label' => 'Meal Types',
        'model_label' => 'Meal Type',
        'plural_model_label' => 'Meal Types',
        'title' => 'Meal Types',
        'pages' => [
            'create' => 'Create Meal Type',
            'edit' => 'Edit Meal Type',
            'list' => 'Meal Types',
        ],
        'fields' => [
            'name' => 'Name',
            'name_placeholder' => 'e.g. Breakfast',
            'slug' => 'Slug',
            'slug_help' => 'e.g. breakfast, lunch, dinner',
            'order_column' => 'Order',
            'is_active' => 'Active',
        ],
        'empty_state' => [
            'heading' => 'No meal types yet',
            'description' => 'Create your first meal type.',
        ],
    ],

    // Meals
    'meals' => [
        'navigation_label' => 'Meals',
        'model_label' => 'Meal',
        'plural_model_label' => 'Meals',
        'title' => 'Meals',
        'pages' => [
            'create' => 'Create Meal',
            'edit' => 'Edit Meal',
            'list' => 'Meals',
        ],
        'fields' => [
            'name' => 'Name',
            'name_placeholder' => 'e.g. Grilled Chicken',
            'description' => 'Description',
            'description_placeholder' => 'Meal description',
            'price' => 'Price',
            'price_placeholder' => '0.00',
            'calories' => 'Calories',
            'protein' => 'Protein',
            'carbs' => 'Carbs',
            'fat' => 'Fat',
            'meal_group_id' => 'Meal Group',
            'categories' => 'Categories',
            'tags' => 'Tags',
            'groups' => 'Groups',
            'image' => 'Main Image',
            'is_active' => 'Active',
            'is_store_product' => 'Store Product',
        ],
        'sections' => [
            'content' => 'Content',
            'details' => 'Details',
            'organization' => 'Organization',
            'media_status' => 'Media & Status',
            'macros' => 'Macros',
        ],
        'empty_state' => [
            'heading' => 'No meals yet',
            'description' => 'Create your first meal.',
        ],
        'relations' => [
            'images' => 'Images',
            'ingredients' => 'Ingredients',
            'offers' => 'Offers',
        ],
    ],

    // Categories
    'categories' => [
        'navigation_label' => 'Categories',
        'model_label' => 'Category',
        'plural_model_label' => 'Categories',
        'title' => 'Categories',
        'pages' => [
            'create' => 'Create Category',
            'edit' => 'Edit Category',
            'list' => 'Categories',
        ],
        'fields' => [
            'name' => 'Name',
            'name_placeholder' => 'e.g. Main Dishes',
            'type' => 'Type',
            'type_meal' => 'Meal',
            'type_blog' => 'Blog',
            'icon' => 'Icon',
            'is_active' => 'Active',
        ],
        'empty_state' => [
            'heading' => 'No categories yet',
            'description' => 'Create your first category.',
        ],
    ],

    // Meal Groups
    'meal_groups' => [
        'navigation_label' => 'Meal Groups',
        'model_label' => 'Meal Group',
        'plural_model_label' => 'Meal Groups',
        'title' => 'Meal Groups',
        'pages' => [
            'create' => 'Create Group',
            'edit' => 'Edit Group',
            'list' => 'Groups',
        ],
        'fields' => [
            'name' => 'Name',
            'name_placeholder' => 'e.g. Weekly Meals',
            'description' => 'Description',
            'is_active' => 'Active',
        ],
        'empty_state' => [
            'heading' => 'No groups yet',
            'description' => 'Create your first group.',
        ],
    ],

    // Meal Tags
    'meal_tags' => [
        'navigation_label' => 'Meal Tags',
        'model_label' => 'Tag',
        'plural_model_label' => 'Meal Tags',
        'title' => 'Meal Tags',
        'pages' => [
            'create' => 'Create Tag',
            'edit' => 'Edit Tag',
            'list' => 'Tags',
        ],
        'fields' => [
            'name' => 'Name',
            'name_placeholder' => 'e.g. Healthy, Quick',
            'is_active' => 'Active',
        ],
        'empty_state' => [
            'heading' => 'No tags yet',
            'description' => 'Create your first tag.',
        ],
    ],

    // Ingredients
    'ingredients' => [
        'navigation_label' => 'Ingredients',
        'model_label' => 'Ingredient',
        'plural_model_label' => 'Ingredients',
        'title' => 'Ingredients',
        'pages' => [
            'create' => 'Create Ingredient',
            'edit' => 'Edit Ingredient',
            'list' => 'Ingredients',
        ],
        'fields' => [
            'name' => 'Name',
            'name_placeholder' => 'e.g. Chicken, Rice',
            'is_active' => 'Active',
            'is_allergen' => 'Is Allergen',
        ],
        'empty_state' => [
            'heading' => 'No ingredients yet',
            'description' => 'Create your first ingredient.',
        ],
    ],

    // Blog Posts
    'blog_posts' => [
        'navigation_label' => 'Blog Posts',
        'model_label' => 'Blog Post',
        'plural_model_label' => 'Blog Posts',
        'title' => 'Blog',
        'pages' => [
            'create' => 'Create Post',
            'edit' => 'Edit Post',
            'list' => 'Blog Posts',
        ],
        'fields' => [
            'title' => 'Title',
            'title_placeholder' => 'Post title',
            'slug' => 'Slug',
            'excerpt' => 'Excerpt',
            'content' => 'Content',
            'cover_image' => 'Cover Image',
            'status' => 'Status',
            'status_draft' => 'Draft',
            'status_published' => 'Published',
            'status_scheduled' => 'Scheduled',
            'status_archived' => 'Archived',
            'published_at' => 'Published At',
            'scheduled_at' => 'Schedule For',
            'is_featured' => 'Featured',
            'allow_comments' => 'Allow Comments',
            'category' => 'Category',
            'author' => 'Author',
            'tags' => 'Tags',
            'reading_time' => 'Reading Time',
            'views_count' => 'Views',
            'likes_count' => 'Likes',
            'meta_title' => 'Meta Title',
            'meta_description' => 'Meta Description',
            'meta_keywords' => 'Meta Keywords',
            'og_title' => 'OG Title',
            'og_description' => 'OG Description',
            'og_image' => 'OG Image',
            'canonical_url' => 'Canonical URL',
            'seo_indexable' => 'Indexable',
            'seo_follow' => 'Follow Links',
        ],
        'sections' => [
            'publication' => 'Publication',
            'organization' => 'Organization',
            'media_stats' => 'Media & Stats',
            'seo_settings' => 'SEO Settings',
            'seo_social' => 'SEO & Social',
        ],
        'empty_state' => [
            'heading' => 'No posts yet',
            'description' => 'Create your first blog post.',
        ],
    ],

    // Blog Categories
    'blog_categories' => [
        'navigation_label' => 'Blog Categories',
        'model_label' => 'Category',
        'plural_model_label' => 'Categories',
        'title' => 'Blog Categories',
        'pages' => [
            'create' => 'Create Category',
            'edit' => 'Edit Category',
            'list' => 'Categories',
        ],
        'fields' => [
            'name' => 'Name',
            'name_placeholder' => 'e.g. Nutrition, Health',
            'slug' => 'Slug',
            'description' => 'Description',
            'order_column' => 'Order',
            'is_active' => 'Active',
            'posts_count' => 'Posts Count',
        ],
        'empty_state' => [
            'heading' => 'No categories yet',
            'description' => 'Create your first category.',
        ],
    ],

    // Blog Tags
    'blog_tags' => [
        'navigation_label' => 'Blog Tags',
        'model_label' => 'Tag',
        'plural_model_label' => 'Tags',
        'title' => 'Blog Tags',
        'pages' => [
            'create' => 'Create Tag',
            'edit' => 'Edit Tag',
            'list' => 'Tags',
        ],
        'fields' => [
            'name' => 'Name',
            'name_placeholder' => 'e.g. Tips, Diet',
            'slug' => 'Slug',
            'is_active' => 'Active',
        ],
        'empty_state' => [
            'heading' => 'No tags yet',
            'description' => 'Create your first tag.',
        ],
    ],

    // FAQs
    'faqs' => [
        'navigation_label' => 'FAQs',
        'model_label' => 'FAQ',
        'plural_model_label' => 'FAQs',
        'title' => 'Frequently Asked Questions',
        'pages' => [
            'create' => 'Create FAQ',
            'edit' => 'Edit FAQ',
            'list' => 'FAQs',
        ],
        'fields' => [
            'question' => 'Question',
            'question_placeholder' => 'Type your question here...',
            'answer' => 'Answer',
            'answer_placeholder' => 'Type your answer here...',
            'category' => 'Category',
            'order_column' => 'Order',
            'is_active' => 'Active',
        ],
        'empty_state' => [
            'heading' => 'No FAQs yet',
            'description' => 'Create your first FAQ.',
        ],
    ],

    // FAQ Categories
    'faq_categories' => [
        'navigation_label' => 'FAQ Categories',
        'model_label' => 'Category',
        'plural_model_label' => 'Categories',
        'title' => 'FAQ Categories',
        'pages' => [
            'create' => 'Create Category',
            'edit' => 'Edit Category',
            'list' => 'Categories',
        ],
        'fields' => [
            'name' => 'Name',
            'name_placeholder' => 'e.g. General, Payment',
            'slug' => 'Slug',
            'order_column' => 'Order',
            'is_active' => 'Active',
        ],
        'empty_state' => [
            'heading' => 'No categories yet',
            'description' => 'Create your first category.',
        ],
    ],

    // FAQ Section Headers
    'faq_section_headers' => [
        'navigation_label' => 'Section Headers',
        'model_label' => 'Section Header',
        'plural_model_label' => 'Section Headers',
        'title' => 'FAQ Section Headers',
        'pages' => [
            'create' => 'Create Header',
            'edit' => 'Edit Header',
            'list' => 'Headers',
        ],
        'fields' => [
            'badge_title' => 'Badge Title',
            'title' => 'Title',
            'subtitle' => 'Subtitle',
            'order_column' => 'Order',
            'is_active' => 'Active',
        ],
        'empty_state' => [
            'heading' => 'No headers yet',
            'description' => 'Create your first section header.',
        ],
    ],

    // Testimonials
    'testimonials' => [
        'navigation_label' => 'Testimonials',
        'model_label' => 'Testimonial',
        'plural_model_label' => 'Testimonials',
        'title' => 'Testimonials',
        'pages' => [
            'create' => 'Add Testimonial',
            'edit' => 'Edit Testimonial',
            'list' => 'Testimonials',
        ],
        'fields' => [
            'author_name' => 'Author Name',
            'author_name_placeholder' => 'e.g. John Doe',
            'author_title' => 'Author Title',
            'author_title_placeholder' => 'e.g. Customer',
            'author_image' => 'Author Image',
            'content' => 'Content',
            'content_placeholder' => 'Write testimonial here...',
            'rating' => 'Rating',
            'rating_1' => '1 Star',
            'rating_2' => '2 Stars',
            'rating_3' => '3 Stars',
            'rating_4' => '4 Stars',
            'rating_5' => '5 Stars',
            'order_column' => 'Order',
            'is_active' => 'Active',
        ],
        'empty_state' => [
            'heading' => 'No testimonials yet',
            'description' => 'Add your first testimonial.',
        ],
    ],

    // Services
    'services' => [
        'navigation_label' => 'Services',
        'model_label' => 'Service',
        'plural_model_label' => 'Services',
        'title' => 'Services',
        'fields' => [
            'name' => 'Name',
            'is_active' => 'Active',
        ],
    ],

    // Menus
    'menus' => [
        'navigation_label' => 'Menus',
        'model_label' => 'Menu',
        'plural_model_label' => 'Menus',
        'title' => 'Menus',
        'fields' => [
            'name' => 'Name',
            'is_active' => 'Active',
        ],
    ],

    // Offers
    'offers' => [
        'navigation_label' => 'Offers',
        'model_label' => 'Offer',
        'plural_model_label' => 'Offers',
        'title' => 'Offers',
        'fields' => [
            'name' => 'Name',
            'discount_percentage' => 'Discount %',
            'discount_amount' => 'Discount Amount',
            'is_active' => 'Active',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
        ],
    ],

    // Common
    'common' => [
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'actions' => 'Actions',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'create' => 'Create',
        'back' => 'Back',
        'search' => 'Search',
        'filter' => 'Filter',
        'all' => 'All',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'yes' => 'Yes',
        'no' => 'No',
        'translations' => 'Translations',
    ],
];
