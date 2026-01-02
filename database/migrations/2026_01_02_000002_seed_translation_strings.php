<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Seed translation strings
     */
    public function up(): void
    {
        // Check if core_translations table exists
        if (!Schema::hasTable('core_translations')) {
            return;
        }

        // Common translation strings that should exist
        $strings = [
            // Navigation
            'Home' => ['ar' => 'الرئيسية', 'es' => 'Inicio', 'fr' => 'Accueil'],
            'About' => ['ar' => 'من نحن', 'es' => 'Acerca de', 'fr' => 'À propos'],
            'Tours' => ['ar' => 'الجولات', 'es' => 'Tours', 'fr' => 'Circuits'],
            'Hotels' => ['ar' => 'الفنادق', 'es' => 'Hoteles', 'fr' => 'Hôtels'],
            'Contact' => ['ar' => 'اتصل بنا', 'es' => 'Contacto', 'fr' => 'Contact'],
            'Blog' => ['ar' => 'المدونة', 'es' => 'Blog', 'fr' => 'Blog'],
            'Destinations' => ['ar' => 'الوجهات', 'es' => 'Destinos', 'fr' => 'Destinations'],
            
            // Common UI
            'Search' => ['ar' => 'بحث', 'es' => 'Buscar', 'fr' => 'Rechercher'],
            'Book Now' => ['ar' => 'احجز الآن', 'es' => 'Reservar ahora', 'fr' => 'Réserver maintenant'],
            'Read More' => ['ar' => 'اقرأ المزيد', 'es' => 'Leer más', 'fr' => 'En savoir plus'],
            'View Details' => ['ar' => 'عرض التفاصيل', 'es' => 'Ver detalles', 'fr' => 'Voir les détails'],
            'Submit' => ['ar' => 'إرسال', 'es' => 'Enviar', 'fr' => 'Soumettre'],
            'Cancel' => ['ar' => 'إلغاء', 'es' => 'Cancelar', 'fr' => 'Annuler'],
            'Save' => ['ar' => 'حفظ', 'es' => 'Guardar', 'fr' => 'Enregistrer'],
            'Delete' => ['ar' => 'حذف', 'es' => 'Eliminar', 'fr' => 'Supprimer'],
            'Edit' => ['ar' => 'تعديل', 'es' => 'Editar', 'fr' => 'Modifier'],
            'Close' => ['ar' => 'إغلاق', 'es' => 'Cerrar', 'fr' => 'Fermer'],
            'Loading...' => ['ar' => 'جاري التحميل...', 'es' => 'Cargando...', 'fr' => 'Chargement...'],
            
            // Authentication
            'Login' => ['ar' => 'تسجيل الدخول', 'es' => 'Iniciar sesión', 'fr' => 'Connexion'],
            'Logout' => ['ar' => 'تسجيل الخروج', 'es' => 'Cerrar sesión', 'fr' => 'Déconnexion'],
            'Register' => ['ar' => 'التسجيل', 'es' => 'Registrarse', 'fr' => "S'inscrire"],
            'Email' => ['ar' => 'البريد الإلكتروني', 'es' => 'Correo electrónico', 'fr' => 'E-mail'],
            'Password' => ['ar' => 'كلمة المرور', 'es' => 'Contraseña', 'fr' => 'Mot de passe'],
            'Forgot Password?' => ['ar' => 'نسيت كلمة المرور؟', 'es' => '¿Olvidaste tu contraseña?', 'fr' => 'Mot de passe oublié?'],
            'Remember me' => ['ar' => 'تذكرني', 'es' => 'Recuérdame', 'fr' => 'Se souvenir de moi'],
            
            // Booking
            'Check In' => ['ar' => 'تسجيل الوصول', 'es' => 'Entrada', 'fr' => 'Arrivée'],
            'Check Out' => ['ar' => 'تسجيل المغادرة', 'es' => 'Salida', 'fr' => 'Départ'],
            'Guests' => ['ar' => 'الضيوف', 'es' => 'Huéspedes', 'fr' => 'Invités'],
            'Adults' => ['ar' => 'بالغين', 'es' => 'Adultos', 'fr' => 'Adultes'],
            'Children' => ['ar' => 'أطفال', 'es' => 'Niños', 'fr' => 'Enfants'],
            'Rooms' => ['ar' => 'الغرف', 'es' => 'Habitaciones', 'fr' => 'Chambres'],
            'Price' => ['ar' => 'السعر', 'es' => 'Precio', 'fr' => 'Prix'],
            'Total' => ['ar' => 'المجموع', 'es' => 'Total', 'fr' => 'Total'],
            'Per Night' => ['ar' => 'لليلة', 'es' => 'Por noche', 'fr' => 'Par nuit'],
            'Per Person' => ['ar' => 'للشخص', 'es' => 'Por persona', 'fr' => 'Par personne'],
            
            // Tour specific
            'Duration' => ['ar' => 'المدة', 'es' => 'Duración', 'fr' => 'Durée'],
            'Group Size' => ['ar' => 'حجم المجموعة', 'es' => 'Tamaño del grupo', 'fr' => 'Taille du groupe'],
            'Languages' => ['ar' => 'اللغات', 'es' => 'Idiomas', 'fr' => 'Langues'],
            'Itinerary' => ['ar' => 'برنامج الرحلة', 'es' => 'Itinerario', 'fr' => 'Itinéraire'],
            'Included' => ['ar' => 'مشمول', 'es' => 'Incluido', 'fr' => 'Inclus'],
            'Not Included' => ['ar' => 'غير مشمول', 'es' => 'No incluido', 'fr' => 'Non inclus'],
            
            // Reviews
            'Reviews' => ['ar' => 'التقييمات', 'es' => 'Reseñas', 'fr' => 'Avis'],
            'Rating' => ['ar' => 'التقييم', 'es' => 'Calificación', 'fr' => 'Note'],
            'Write a Review' => ['ar' => 'اكتب تقييم', 'es' => 'Escribir una reseña', 'fr' => 'Écrire un avis'],
            
            // Footer
            'About Us' => ['ar' => 'من نحن', 'es' => 'Sobre nosotros', 'fr' => 'À propos de nous'],
            'Privacy Policy' => ['ar' => 'سياسة الخصوصية', 'es' => 'Política de privacidad', 'fr' => 'Politique de confidentialité'],
            'Terms & Conditions' => ['ar' => 'الشروط والأحكام', 'es' => 'Términos y condiciones', 'fr' => 'Termes et conditions'],
            'FAQ' => ['ar' => 'الأسئلة الشائعة', 'es' => 'Preguntas frecuentes', 'fr' => 'FAQ'],
            'Support' => ['ar' => 'الدعم', 'es' => 'Soporte', 'fr' => 'Support'],
            'All Rights Reserved' => ['ar' => 'جميع الحقوق محفوظة', 'es' => 'Todos los derechos reservados', 'fr' => 'Tous droits réservés'],
            
            // Messages
            'Welcome' => ['ar' => 'مرحبا', 'es' => 'Bienvenido', 'fr' => 'Bienvenue'],
            'Thank you' => ['ar' => 'شكرا لك', 'es' => 'Gracias', 'fr' => 'Merci'],
            'Success' => ['ar' => 'نجاح', 'es' => 'Éxito', 'fr' => 'Succès'],
            'Error' => ['ar' => 'خطأ', 'es' => 'Error', 'fr' => 'Erreur'],
            'Warning' => ['ar' => 'تحذير', 'es' => 'Advertencia', 'fr' => 'Avertissement'],
            'No results found' => ['ar' => 'لم يتم العثور على نتائج', 'es' => 'No se encontraron resultados', 'fr' => 'Aucun résultat trouvé'],
            
            // Hero Section
            'Explore the World' => ['ar' => 'استكشف العالم', 'es' => 'Explora el mundo', 'fr' => 'Explorez le monde'],
            'Discover Amazing Places' => ['ar' => 'اكتشف أماكن مذهلة', 'es' => 'Descubre lugares increíbles', 'fr' => 'Découvrez des endroits incroyables'],
            'Find your next adventure' => ['ar' => 'اعثر على مغامرتك القادمة', 'es' => 'Encuentra tu próxima aventura', 'fr' => 'Trouvez votre prochaine aventure'],
            'Start your journey' => ['ar' => 'ابدأ رحلتك', 'es' => 'Comienza tu viaje', 'fr' => 'Commencez votre voyage'],
            
            // Popular sections
            'Popular Tours' => ['ar' => 'الجولات الشائعة', 'es' => 'Tours populares', 'fr' => 'Circuits populaires'],
            'Featured Destinations' => ['ar' => 'الوجهات المميزة', 'es' => 'Destinos destacados', 'fr' => 'Destinations en vedette'],
            'Special Offers' => ['ar' => 'العروض الخاصة', 'es' => 'Ofertas especiales', 'fr' => 'Offres spéciales'],
            'Why Choose Us' => ['ar' => 'لماذا تختارنا', 'es' => '¿Por qué elegirnos?', 'fr' => 'Pourquoi nous choisir'],
            'Our Services' => ['ar' => 'خدماتنا', 'es' => 'Nuestros servicios', 'fr' => 'Nos services'],
            'Testimonials' => ['ar' => 'شهادات العملاء', 'es' => 'Testimonios', 'fr' => 'Témoignages'],
            
            // Contact
            'Contact Us' => ['ar' => 'اتصل بنا', 'es' => 'Contáctenos', 'fr' => 'Contactez-nous'],
            'Send Message' => ['ar' => 'إرسال رسالة', 'es' => 'Enviar mensaje', 'fr' => 'Envoyer un message'],
            'Your Name' => ['ar' => 'اسمك', 'es' => 'Tu nombre', 'fr' => 'Votre nom'],
            'Your Email' => ['ar' => 'بريدك الإلكتروني', 'es' => 'Tu correo', 'fr' => 'Votre e-mail'],
            'Subject' => ['ar' => 'الموضوع', 'es' => 'Asunto', 'fr' => 'Sujet'],
            'Message' => ['ar' => 'الرسالة', 'es' => 'Mensaje', 'fr' => 'Message'],
            'Phone' => ['ar' => 'الهاتف', 'es' => 'Teléfono', 'fr' => 'Téléphone'],
            'Address' => ['ar' => 'العنوان', 'es' => 'Dirección', 'fr' => 'Adresse'],
        ];

        $now = now();

        foreach ($strings as $original => $translations) {
            // Check if raw string already exists
            $existing = DB::table('core_translations')
                ->where('locale', 'raw')
                ->where('string', $original)
                ->first();

            if ($existing) {
                $parentId = $existing->id;
            } else {
                // Insert raw string (original English)
                $parentId = DB::table('core_translations')->insertGetId([
                    'locale' => 'raw',
                    'string' => $original,
                    'parent_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Insert English translation (same as original)
            $existingEn = DB::table('core_translations')
                ->where('locale', 'en')
                ->where('parent_id', $parentId)
                ->first();

            if (!$existingEn) {
                DB::table('core_translations')->insert([
                    'locale' => 'en',
                    'string' => $original,
                    'parent_id' => $parentId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Insert other translations
            foreach ($translations as $locale => $translation) {
                $existingTrans = DB::table('core_translations')
                    ->where('locale', $locale)
                    ->where('parent_id', $parentId)
                    ->first();

                if (!$existingTrans) {
                    DB::table('core_translations')->insert([
                        'locale' => $locale,
                        'string' => $translation,
                        'parent_id' => $parentId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't remove translations on rollback
    }
};
