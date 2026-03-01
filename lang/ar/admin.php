<?php

return [
    // Navigation Groups
    'navigation_groups' => [
        'content' => 'المحتوى',
        'blog' => 'المدونة',
        'meal_management' => 'إدارة الوجبات',
        'meal_plans' => 'خطط الوجبات',
        'users_permissions' => 'المستخدمين والصلاحيات',
        'faq' => 'الأسئلة الشائعة',
        'testimonials' => 'آراء العملاء',
        'settings' => 'الإعدادات',
    ],

    // Menu Items
    'menu_items' => [
        'navigation_label' => 'عناصر القائمة',
        'model_label' => 'عنصر القائمة',
        'plural_model_label' => 'عناصر القائمة',
        'title' => 'قائمة الموقع',
        'pages' => [
            'create' => 'إضافة عنصر',
            'edit' => 'تعديل عنصر القائمة',
            'list' => 'عناصر القائمة',
        ],
        'fields' => [
            'label' => 'التسمية',
            'label_placeholder' => 'مثال: من نحن، اتصل بنا',
            'url' => 'الرابط',
            'url_placeholder' => '/about أو https://...',
            'route_name' => 'اسم المسار',
            'route_name_placeholder' => 'اختياري',
            'target' => 'فتح في',
            'target_self' => 'نفس النافذة',
            'target_blank' => 'نافذة جديدة',
            'order' => 'الترتيب',
            'is_active' => 'نشط',
            'parent' => 'العنصر الأب',
        ],
        'empty_state' => [
            'heading' => 'لا توجد عناصر قائمة بعد',
            'description' => 'أضف أول عنصر قائمة.',
        ],
    ],

    // Hero Sections
    'hero_sections' => [
        'navigation_label' => 'أقسام البطل',
        'model_label' => 'قسم البطل',
        'plural_model_label' => 'أقسام البطل',
        'title' => 'أقسام البطل',
        'pages' => [
            'create' => 'إضافة قسم بطل',
            'edit' => 'تعديل قسم البطل',
            'list' => 'أقسام البطل',
        ],
        'fields' => [
            'title' => 'العنوان الرئيسي',
            'title_placeholder' => 'وجبات صحية يومياً...',
            'subtitle' => 'العنوان الفرعي',
            'subtitle_placeholder' => 'وصف قصير',
            'cta_text' => 'نص الزر الأساسي',
            'cta_text_placeholder' => 'متجر التطبيقات',
            'cta_secondary_text' => 'نص الزر الثانوي',
            'cta_secondary_text_placeholder' => 'جوجل بلاي',
            'image_desktop' => 'صورة سطح المكتب',
            'image_mobile' => 'صورة الموبايل',
            'app_store_url' => 'رابط متجر التطبيقات',
            'play_store_url' => 'رابط جوجل بلاي',
            'order' => 'الترتيب',
            'is_active' => 'نشط',
        ],
        'empty_state' => [
            'heading' => 'لا توجد أقسام بطل بعد',
            'description' => 'أنشئ أول قسم بطل.',
        ],
    ],

    // Features
    'features' => [
        'navigation_label' => 'المميزات',
        'model_label' => 'ميزة',
        'plural_model_label' => 'المميزات',
        'title' => 'المميزات',
        'pages' => [
            'create' => 'إضافة ميزة',
            'edit' => 'تعديل الميزة',
            'list' => 'المميزات',
        ],
        'fields' => [
            'title' => 'العنوان',
            'title_placeholder' => 'مثال: اطلب وجبتك',
            'description' => 'الوصف',
            'description_placeholder' => 'وصف قصير',
            'image' => 'الصورة',
            'icon' => 'الأيقونة',
            'order' => 'الترتيب',
            'is_active' => 'نشط',
        ],
        'empty_state' => [
            'heading' => 'لا توجد مميزات بعد',
            'description' => 'أضف أول ميزة.',
        ],
    ],

    // Settings
    'settings' => [
        'navigation_label' => 'الإعدادات',
        'title' => 'الإعدادات',
        'groups' => [
            'general' => 'عام',
            'header' => 'الهيدر',
            'footer' => 'الفوتر',
            'social' => 'روابط التواصل',
        ],
        'fields' => [
            'site_name' => 'اسم الموقع',
            'logo_header' => 'شعار الهيدر',
            'logo_footer' => 'شعار الفوتر',
            'favicon' => 'الأيقونة المفضلة',
            'contact_email' => 'البريد للتواصل',
            'copyright' => 'نص حقوق النشر',
            'footer_description' => 'وصف الفوتر',
            'app_links' => 'روابط التطبيق',
        ],
        'messages' => [
            'saved' => 'تم حفظ الإعدادات بنجاح.',
        ],
    ],

    // Roles
    'roles' => [
        'navigation_label' => 'الأدوار',
        'model_label' => 'دور',
        'plural_model_label' => 'الأدوار',
        'title' => 'الأدوار',
        'pages' => [
            'create' => 'إنشاء دور',
            'edit' => 'تعديل الدور',
            'list' => 'الأدوار',
        ],
        'fields' => [
            'name' => 'الاسم',
            'name_placeholder' => 'مثال: مدير، محرر',
            'guard_name' => 'الحارس',
            'permissions' => 'الصلاحيات',
        ],
        'empty_state' => [
            'heading' => 'لا توجد أدوار بعد',
            'description' => 'أنشئ أول دور.',
        ],
    ],

    // Permissions
    'permissions' => [
        'navigation_label' => 'الصلاحيات',
        'model_label' => 'صلاحية',
        'plural_model_label' => 'الصلاحيات',
        'title' => 'الصلاحيات',
        'pages' => [
            'create' => 'إنشاء صلاحية',
            'edit' => 'تعديل الصلاحية',
            'list' => 'الصلاحيات',
        ],
        'fields' => [
            'name' => 'الاسم',
            'name_placeholder' => 'مثال: create-users',
            'guard_name' => 'الحارس',
        ],
        'empty_state' => [
            'heading' => 'لا توجد صلاحيات بعد',
            'description' => 'أنشئ أول صلاحية.',
        ],
    ],

    // Users
    'users' => [
        'navigation_label' => 'المستخدمون',
        'model_label' => 'مستخدم',
        'plural_model_label' => 'المستخدمون',
        'title' => 'المستخدمون',
        'pages' => [
            'create' => 'إنشاء مستخدم',
            'edit' => 'تعديل المستخدم',
            'view' => 'عرض المستخدم',
            'list' => 'المستخدمون',
        ],
        'fields' => [
            'name' => 'الاسم',
            'name_placeholder' => 'أدخل الاسم الكامل',
            'name_help' => 'مطلوب. حرفان على الأقل.',
            'email' => 'البريد الإلكتروني',
            'email_placeholder' => 'أدخل البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'password_placeholder' => 'أدخل كلمة المرور',
            'roles' => 'الأدوار',
            'is_active' => 'نشط',
            'email_verified_at' => 'تم التحقق من البريد',
        ],
        'actions' => [
            'create' => 'إنشاء مستخدم',
            'approve' => 'موافقة',
            'delete' => 'حذف',
        ],
        'messages' => [
            'created' => 'تم إنشاء المستخدم بنجاح.',
            'updated' => 'تم تحديث المستخدم بنجاح.',
            'deleted' => 'تم حذف المستخدم.',
        ],
        'filters' => [
            'is_active' => 'نشط',
        ],
        'empty_state' => [
            'heading' => 'لا يوجد مستخدمون بعد',
            'description' => 'أنشئ أول مستخدم للبدء.',
        ],
    ],

    // Plans
    'plans' => [
        'navigation_label' => 'الخطط',
        'model_label' => 'خطة',
        'plural_model_label' => 'الخطط',
        'title' => 'خطط الوجبات',
        'pages' => [
            'create' => 'إنشاء خطة',
            'edit' => 'تعديل الخطة',
            'list' => 'الخطط',
        ],
        'fields' => [
            'name' => 'الاسم',
            'name_placeholder' => 'مثال: خطة عالية البروتين',
            'subtitle' => 'العنوان الفرعي',
            'subtitle_placeholder' => 'شعار قصير',
            'description' => 'الوصف',
            'description_placeholder' => 'وصف كامل للخطة',
            'ingredients' => 'المكونات',
            'ingredients_placeholder' => 'قائمة المكونات',
            'benefits' => 'الفوائد',
            'benefits_placeholder' => 'فوائد الخطة',
            'hero_image' => 'الصورة الرئيسية',
            'is_active' => 'نشط',
            'show_in_app' => 'إظهار في التطبيق',
            'order_column' => 'الترتيب',
        ],
        'empty_state' => [
            'heading' => 'لا توجد خطط بعد',
            'description' => 'أنشئ أول خطة وجبات.',
        ],
        'tabs' => [
            'images' => 'معرض الصور',
            'calories' => 'خيارات السعرات',
            'durations' => 'المدة والأسعار',
            'menus' => 'القوائم',
            'categories' => 'التصنيفات',
            'meal_types' => 'أنواع الوجبات',
        ],
    ],

    // Plan Categories
    'plan_categories' => [
        'navigation_label' => 'تصنيفات الخطط',
        'model_label' => 'تصنيف',
        'plural_model_label' => 'التصنيفات',
        'title' => 'تصنيفات الخطط',
        'pages' => [
            'create' => 'إنشاء تصنيف',
            'edit' => 'تعديل التصنيف',
            'list' => 'التصنيفات',
        ],
        'fields' => [
            'name' => 'الاسم',
            'name_placeholder' => 'مثال: عالي البروتين',
            'slug' => 'الرابط المختصر',
            'slug_help' => 'يستخدم في الروابط (مثال: high-protein)',
            'is_active' => 'نشط',
            'order_column' => 'الترتيب',
        ],
        'empty_state' => [
            'heading' => 'لا توجد تصنيفات بعد',
            'description' => 'أنشئ أول تصنيف.',
        ],
    ],

    // Meal Types
    'meal_types' => [
        'navigation_label' => 'أنواع الوجبات',
        'model_label' => 'نوع وجبة',
        'plural_model_label' => 'أنواع الوجبات',
        'title' => 'أنواع الوجبات',
        'pages' => [
            'create' => 'إنشاء نوع وجبة',
            'edit' => 'تعديل نوع الوجبة',
            'list' => 'أنواع الوجبات',
        ],
        'fields' => [
            'name' => 'الاسم',
            'name_placeholder' => 'مثال: فطور',
            'slug' => 'الرابط المختصر',
            'slug_help' => 'مثال: breakfast, lunch, dinner',
            'order_column' => 'الترتيب',
            'is_active' => 'نشط',
        ],
        'empty_state' => [
            'heading' => 'لا توجد أنواع وجبات بعد',
            'description' => 'أنشئ أول نوع وجبة.',
        ],
    ],

    // Meals
    'meals' => [
        'navigation_label' => 'الوجبات',
        'model_label' => 'وجبة',
        'plural_model_label' => 'الوجبات',
        'title' => 'الوجبات',
        'pages' => [
            'create' => 'إنشاء وجبة',
            'edit' => 'تعديل الوجبة',
            'list' => 'الوجبات',
        ],
        'fields' => [
            'name' => 'الاسم',
            'name_placeholder' => 'مثال: دجاج مشوي',
            'description' => 'الوصف',
            'description_placeholder' => 'وصف الوجبة',
            'price' => 'السعر',
            'price_placeholder' => '0.00',
            'calories' => 'السعرات الحرارية',
            'protein' => 'البروتين',
            'carbs' => 'الكربوهيدرات',
            'fat' => 'الدهون',
            'meal_group_id' => 'مجموعة الوجبات',
            'categories' => 'التصنيفات',
            'tags' => 'الوسوم',
            'groups' => 'المجموعات',
            'image' => 'الصورة الرئيسية',
            'is_active' => 'نشط',
            'is_store_product' => 'منتج المتجر',
        ],
        'sections' => [
            'content' => 'المحتوى',
            'details' => 'التفاصيل',
            'organization' => 'التنظيم',
            'media_status' => 'الوسائط والحالة',
            'macros' => 'العناصر الغذائية',
        ],
        'empty_state' => [
            'heading' => 'لا توجد وجبات بعد',
            'description' => 'أنشئ أول وجبة.',
        ],
        'relations' => [
            'images' => 'الصور',
            'ingredients' => 'المكونات',
            'offers' => 'العروض',
        ],
    ],

    // Categories
    'categories' => [
        'navigation_label' => 'التصنيفات',
        'model_label' => 'تصنيف',
        'plural_model_label' => 'التصنيفات',
        'title' => 'التصنيفات',
        'pages' => [
            'create' => 'إنشاء تصنيف',
            'edit' => 'تعديل التصنيف',
            'list' => 'التصنيفات',
        ],
        'fields' => [
            'name' => 'الاسم',
            'name_placeholder' => 'مثال: وجبات رئيسية',
            'type' => 'النوع',
            'type_meal' => 'وجبة',
            'type_blog' => 'مدونة',
            'icon' => 'الأيقونة',
            'is_active' => 'نشط',
        ],
        'empty_state' => [
            'heading' => 'لا توجد تصنيفات بعد',
            'description' => 'أنشئ أول تصنيف.',
        ],
    ],

    // Meal Groups
    'meal_groups' => [
        'navigation_label' => 'مجموعات الوجبات',
        'model_label' => 'مجموعة',
        'plural_model_label' => 'مجموعات الوجبات',
        'title' => 'مجموعات الوجبات',
        'pages' => [
            'create' => 'إنشاء مجموعة',
            'edit' => 'تعديل المجموعة',
            'list' => 'المجموعات',
        ],
        'fields' => [
            'name' => 'الاسم',
            'name_placeholder' => 'مثال: وجبات الأسبوع',
            'description' => 'الوصف',
            'is_active' => 'نشط',
        ],
        'empty_state' => [
            'heading' => 'لا توجد مجموعات بعد',
            'description' => 'أنشئ أول مجموعة.',
        ],
    ],

    // Meal Tags
    'meal_tags' => [
        'navigation_label' => 'وسوم الوجبات',
        'model_label' => 'وسم',
        'plural_model_label' => 'وسوم الوجبات',
        'title' => 'وسوم الوجبات',
        'pages' => [
            'create' => 'إنشاء وسم',
            'edit' => 'تعديل الوسم',
            'list' => 'الوسوم',
        ],
        'fields' => [
            'name' => 'الاسم',
            'name_placeholder' => 'مثال: صحي، سريع',
            'is_active' => 'نشط',
        ],
        'empty_state' => [
            'heading' => 'لا توجد وسوم بعد',
            'description' => 'أنشئ أول وسم.',
        ],
    ],

    // Ingredients
    'ingredients' => [
        'navigation_label' => 'المكونات',
        'model_label' => 'مكون',
        'plural_model_label' => 'المكونات',
        'title' => 'المكونات',
        'pages' => [
            'create' => 'إنشاء مكون',
            'edit' => 'تعديل المكون',
            'list' => 'المكونات',
        ],
        'fields' => [
            'name' => 'الاسم',
            'name_placeholder' => 'مثال: دجاج، أرز',
            'is_active' => 'نشط',
            'is_allergen' => 'مسبب حساسية',
        ],
        'empty_state' => [
            'heading' => 'لا توجد مكونات بعد',
            'description' => 'أنشئ أول مكون.',
        ],
    ],

    // Blog Posts
    'blog_posts' => [
        'navigation_label' => 'المقالات',
        'model_label' => 'مقالة',
        'plural_model_label' => 'المقالات',
        'title' => 'المدونة',
        'pages' => [
            'create' => 'إنشاء مقالة',
            'edit' => 'تعديل المقالة',
            'list' => 'المقالات',
        ],
        'fields' => [
            'title' => 'العنوان',
            'title_placeholder' => 'عنوان المقالة',
            'slug' => 'الرابط المختصر',
            'excerpt' => 'الملخص',
            'content' => 'المحتوى',
            'cover_image' => 'صورة الغلاف',
            'status' => 'الحالة',
            'status_draft' => 'مسودة',
            'status_published' => 'منشور',
            'status_scheduled' => 'مجدول',
            'status_archived' => 'مؤرشف',
            'published_at' => 'تاريخ النشر',
            'scheduled_at' => 'مجدول لـ',
            'is_featured' => 'مميز',
            'allow_comments' => 'السماح بالتعليقات',
            'category' => 'التصنيف',
            'author' => 'الكاتب',
            'tags' => 'الوسوم',
            'reading_time' => 'وقت القراءة',
            'views_count' => 'المشاهدات',
            'likes_count' => 'الإعجابات',
            'meta_title' => 'عنوان SEO',
            'meta_description' => 'وصف SEO',
            'meta_keywords' => 'كلمات مفتاحية',
            'og_title' => 'عنوان Open Graph',
            'og_description' => 'وصف Open Graph',
            'og_image' => 'صورة Open Graph',
            'canonical_url' => 'الرابط الأساسي',
            'seo_indexable' => 'قابل للفهرسة',
            'seo_follow' => 'متابعة الروابط',
        ],
        'sections' => [
            'publication' => 'النشر',
            'organization' => 'التنظيم',
            'media_stats' => 'الوسائط والإحصائيات',
            'seo_settings' => 'إعدادات SEO',
            'seo_social' => 'SEO والتواصل',
        ],
        'empty_state' => [
            'heading' => 'لا توجد مقالات بعد',
            'description' => 'أنشئ أول مقالة.',
        ],
    ],

    // Blog Categories
    'blog_categories' => [
        'navigation_label' => 'تصنيفات المدونة',
        'model_label' => 'تصنيف',
        'plural_model_label' => 'تصنيفات المدونة',
        'title' => 'تصنيفات المدونة',
        'pages' => [
            'create' => 'إنشاء تصنيف',
            'edit' => 'تعديل التصنيف',
            'list' => 'التصنيفات',
        ],
        'fields' => [
            'name' => 'الاسم',
            'name_placeholder' => 'مثال: تغذية، صحة',
            'slug' => 'الرابط المختصر',
            'description' => 'الوصف',
            'order_column' => 'الترتيب',
            'is_active' => 'نشط',
            'posts_count' => 'عدد المقالات',
        ],
        'empty_state' => [
            'heading' => 'لا توجد تصنيفات بعد',
            'description' => 'أنشئ أول تصنيف.',
        ],
    ],

    // Blog Tags
    'blog_tags' => [
        'navigation_label' => 'وسوم المدونة',
        'model_label' => 'وسم',
        'plural_model_label' => 'وسوم المدونة',
        'title' => 'وسوم المدونة',
        'pages' => [
            'create' => 'إنشاء وسم',
            'edit' => 'تعديل الوسم',
            'list' => 'الوسوم',
        ],
        'fields' => [
            'name' => 'الاسم',
            'name_placeholder' => 'مثال: نصائح، رجيم',
            'slug' => 'الرابط المختصر',
            'is_active' => 'نشط',
        ],
        'empty_state' => [
            'heading' => 'لا توجد وسوم بعد',
            'description' => 'أنشئ أول وسم.',
        ],
    ],

    // FAQs
    'faqs' => [
        'navigation_label' => 'الأسئلة الشائعة',
        'model_label' => 'سؤال',
        'plural_model_label' => 'الأسئلة الشائعة',
        'title' => 'الأسئلة الشائعة',
        'pages' => [
            'create' => 'إنشاء سؤال',
            'edit' => 'تعديل السؤال',
            'list' => 'الأسئلة',
        ],
        'fields' => [
            'question' => 'السؤال',
            'question_placeholder' => 'اكتب السؤال هنا...',
            'answer' => 'الإجابة',
            'answer_placeholder' => 'اكتب الإجابة هنا...',
            'category' => 'التصنيف',
            'order_column' => 'الترتيب',
            'is_active' => 'نشط',
        ],
        'empty_state' => [
            'heading' => 'لا توجد أسئلة بعد',
            'description' => 'أنشئ أول سؤال.',
        ],
    ],

    // FAQ Categories
    'faq_categories' => [
        'navigation_label' => 'تصنيفات الأسئلة',
        'model_label' => 'تصنيف',
        'plural_model_label' => 'تصنيفات الأسئلة',
        'title' => 'تصنيفات الأسئلة',
        'pages' => [
            'create' => 'إنشاء تصنيف',
            'edit' => 'تعديل التصنيف',
            'list' => 'التصنيفات',
        ],
        'fields' => [
            'name' => 'الاسم',
            'name_placeholder' => 'مثال: عام، الدفع',
            'slug' => 'الرابط المختصر',
            'order_column' => 'الترتيب',
            'is_active' => 'نشط',
        ],
        'empty_state' => [
            'heading' => 'لا توجد تصنيفات بعد',
            'description' => 'أنشئ أول تصنيف.',
        ],
    ],

    // FAQ Section Headers
    'faq_section_headers' => [
        'navigation_label' => 'رؤوس الأقسام',
        'model_label' => 'رأس القسم',
        'plural_model_label' => 'رؤوس الأقسام',
        'title' => 'رؤوس أقسام الأسئلة',
        'pages' => [
            'create' => 'إنشاء رأس قسم',
            'edit' => 'تعديل رأس القسم',
            'list' => 'الرؤوس',
        ],
        'fields' => [
            'badge_title' => 'عنوان الشارة',
            'title' => 'العنوان',
            'subtitle' => 'العنوان الفرعي',
            'order_column' => 'الترتيب',
            'is_active' => 'نشط',
        ],
        'empty_state' => [
            'heading' => 'لا توجد رؤوس أقسام بعد',
            'description' => 'أنشئ أول رأس قسم.',
        ],
    ],

    // Testimonials
    'testimonials' => [
        'navigation_label' => 'آراء العملاء',
        'model_label' => 'رأي',
        'plural_model_label' => 'آراء العملاء',
        'title' => 'آراء العملاء',
        'pages' => [
            'create' => 'إضافة رأي',
            'edit' => 'تعديل الرأي',
            'list' => 'الآراء',
        ],
        'fields' => [
            'author_name' => 'اسم الكاتب',
            'author_name_placeholder' => 'مثال: أحمد علي',
            'author_title' => 'الصفة',
            'author_title_placeholder' => 'مثال: عميل',
            'author_image' => 'صورة الكاتب',
            'content' => 'المحتوى',
            'content_placeholder' => 'اكتب الرأي هنا...',
            'rating' => 'التقييم',
            'rating_1' => 'نجمة واحدة',
            'rating_2' => 'نجمتان',
            'rating_3' => '3 نجمات',
            'rating_4' => '4 نجمات',
            'rating_5' => '5 نجمات',
            'order_column' => 'الترتيب',
            'is_active' => 'نشط',
        ],
        'empty_state' => [
            'heading' => 'لا توجد آراء بعد',
            'description' => 'أضف أول رأي.',
        ],
    ],

    // Services
    'services' => [
        'navigation_label' => 'الخدمات',
        'model_label' => 'خدمة',
        'plural_model_label' => 'الخدمات',
        'title' => 'الخدمات',
        'fields' => [
            'name' => 'الاسم',
            'is_active' => 'نشط',
        ],
    ],

    // Menus
    'menus' => [
        'navigation_label' => 'القوائم',
        'model_label' => 'قائمة',
        'plural_model_label' => 'القوائم',
        'title' => 'القوائم',
        'fields' => [
            'name' => 'الاسم',
            'is_active' => 'نشط',
        ],
    ],

    // Offers
    'offers' => [
        'navigation_label' => 'العروض',
        'model_label' => 'عرض',
        'plural_model_label' => 'العروض',
        'title' => 'العروض',
        'fields' => [
            'name' => 'الاسم',
            'discount_percentage' => 'نسبة الخصم %',
            'discount_amount' => 'قيمة الخصم',
            'is_active' => 'نشط',
            'start_date' => 'تاريخ البدء',
            'end_date' => 'تاريخ الانتهاء',
        ],
    ],

    // Common
    'common' => [
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
        'actions' => 'الإجراءات',
        'save' => 'حفظ',
        'cancel' => 'إلغاء',
        'delete' => 'حذف',
        'edit' => 'تعديل',
        'create' => 'إنشاء',
        'back' => 'رجوع',
        'search' => 'بحث',
        'filter' => 'تصفية',
        'all' => 'الكل',
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'yes' => 'نعم',
        'no' => 'لا',
        'translations' => 'الترجمات',
    ],
];
