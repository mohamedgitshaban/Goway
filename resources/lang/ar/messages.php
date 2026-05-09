<?php

return [
    // ── Coupons ───────────────────────────────────────────────────────────────
    'coupon_not_found_or_inactive' => 'الكوبون غير موجود أو غير مفعّل',
    'coupon_not_valid'             => 'الكوبون غير صالح لهذه الرحلة',
    'coupon_valid'                 => 'تم تطبيق الكوبون بنجاح',

    // ── Auth – shared ─────────────────────────────────────────────────────────
    'user_not_found'               => 'المستخدم غير موجود',
    'account_deactivated'          => 'حسابك موقوف.',
    'otp_sent_to_phone'            => 'تم إرسال رمز التحقق إلى الهاتف',
    'otp_sent_to_email'            => 'تم إرسال رمز التحقق إلى البريد الإلكتروني',
    'invalid_or_expired_otp'       => 'رمز التحقق غير صحيح أو منتهي الصلاحية',
    'invalid_credentials'          => 'بيانات الدخول غير صحيحة',
    'logged_out'                   => 'تم تسجيل الخروج',
    'profile_updated'              => 'تم تحديث الملف الشخصي بنجاح',
    'account_deleted'              => 'تم حذف الحساب بنجاح',
    'password_reset_successful'    => 'تم إعادة تعيين كلمة المرور بنجاح',

    // ── Auth – driver ─────────────────────────────────────────────────────────
    'phone_registered_as_client'   => 'هذا الهاتف مسجّل كحساب عميل.',
    'phone_used_by_client'         => 'هذا الهاتف مستخدم بالفعل كحساب عميل.',
    'registration_successful'      => 'تم التسجيل بنجاح، تم إرسال رمز التحقق إلى الهاتف',
    'driver_online'                => 'السائق متاح الآن',
    'driver_offline'               => 'السائق غير متاح الآن',

    // ── Auth – client ─────────────────────────────────────────────────────────
    'phone_registered_as_driver'   => 'هذا الهاتف مسجّل كحساب سائق.',
    'phone_used_by_driver'         => 'هذا الهاتف مستخدم بالفعل كحساب سائق.',

    // ── General ───────────────────────────────────────────────────────────────
    'unauthorized'                 => 'غير مصرح',
    'not_your_trip'                => 'هذه الرحلة ليست لك',
    'created_successfully'         => 'تم الإنشاء بنجاح',
    'updated_successfully'         => 'تم التحديث بنجاح',
    'deleted_successfully'         => 'تم الحذف بنجاح',

    // ── Trips ─────────────────────────────────────────────────────────────────
    'trip_created'                 => 'تم إنشاء الرحلة بنجاح',
    'trip_not_ready_for_arrival'   => 'الرحلة غير جاهزة للوصول',
    'driver_arrived'               => 'تم تسجيل وصول السائق',
    'trip_cannot_start'            => 'لا يمكن بدء الرحلة في هذه المرحلة',
    'trip_started'                 => 'تم بدء الرحلة بنجاح',
    'trip_cannot_complete'         => 'لا يمكن إنهاء الرحلة في هذه المرحلة',
    'trip_completed'               => 'تم إنهاء الرحلة بنجاح',
    'trip_cannot_cancel'           => 'لا يمكن إلغاء الرحلة في هذه المرحلة',
    'cannot_negotiate'             => 'لا يمكن التفاوض في هذه المرحلة',
    'offer_sent'                   => 'تم إرسال العرض إلى العميل',
    'trip_not_paid'                => 'لم يتم دفع الرحلة بعد',
    'client_rated'                 => 'تم تقييم العميل بنجاح',
    'cannot_accept_offer'          => 'لا يمكن قبول العرض في هذه المرحلة',
    'offer_accepted'               => 'تم قبول العرض',
    'offer_rejected'               => 'تم رفض العرض',
    'counter_offer_sent'           => 'تم إرسال العرض المضاد',
    'no_driver_assigned'           => 'لم يتم تعيين سائق بعد',
    'driver_rated'                 => 'تم تقييم السائق بنجاح',

    // ── Trip types (BaseController) ───────────────────────────────────────────
    'trip_type_not_found'          => 'نوع الرحلة غير موجود',
    'trip_type_restored'           => 'تم استعادة نوع الرحلة',
    'restore_not_supported'        => 'الاستعادة غير مدعومة لهذا النموذج',

    // ── Driver location ───────────────────────────────────────────────────────
    'not_a_driver'                 => 'لست سائقاً',
    'driver_not_active'            => 'السائق غير نشط',
    'driver_offline_location'      => 'السائق غير متاح. يرجى الاتصال لتحديث الموقع.',
    'location_unchanged'           => 'الموقع لم يتغير — لم يُرسل أي حدث',

    // ── Vehicles ──────────────────────────────────────────────────────────────
    'vehicle_not_found'            => 'المركبة غير موجودة',
    'vehicle_accepted'             => 'تم قبول المركبة بنجاح',
    'vehicle_rejected'             => 'تم رفض المركبة',

    // ── Driver documents ──────────────────────────────────────────────────────
    'driver_not_found'             => 'السائق غير موجود',
    'documents_submitted'          => 'تم إرسال المستندات بنجاح وهي قيد المراجعة',
    'document_not_found'           => 'المستند غير موجود',
    'documents_accepted'           => 'تم قبول المستندات',
    'documents_rejected'           => 'تم رفض المستندات',

    // ── Notifications ─────────────────────────────────────────────────────────
    'notification_marked_read'     => 'تم تحديد الإشعار كمقروء',
    'all_notifications_read'       => 'تم تحديد جميع الإشعارات كمقروءة',
    'fcm_token_updated'            => 'تم تحديث رمز FCM بنجاح',

    // ── Safety access ─────────────────────────────────────────────────────────
    'safety_access_updated'        => 'تم تحديث إعدادات السلامة بنجاح',
    'safety_location_disabled'     => 'ميزة موقع السلامة غير مفعّلة لحسابك',
    'trip_not_active_safety'       => 'الرحلة غير نشطة لتتبع السلامة المباشر',
    'safety_location_stored'       => 'تم حفظ موقع السلامة',
    'safety_voice_disabled'        => 'ميزة تسجيل صوت السلامة غير مفعّلة لحسابك',
    'trip_not_active_voice'        => 'الرحلة غير نشطة لتسجيل صوت السلامة',
    'voice_session_started'        => 'تم بدء جلسة تسجيل الصوت للسلامة',
    'trip_not_active_chunk'        => 'الرحلة غير نشطة لتسجيل المقطع الصوتي',
    'voice_chunk_stored'           => 'تم حفظ المقطع الصوتي',
    'recording_not_yours'          => 'لا ينتمي هذا التسجيل إلى رحلتك أو حسابك',
    'voice_session_finished'       => 'تم إنهاء جلسة تسجيل الصوت للسلامة',

    // ── Trusted contacts ──────────────────────────────────────────────────────
    'trusted_contacts_limit'       => 'يمكنك إضافة :max جهات اتصال موثوقة كحد أقصى.',
    'trusted_contact_added'        => 'تم إضافة جهة الاتصال الموثوقة بنجاح',
    'trusted_contact_not_found'    => 'جهة الاتصال الموثوقة غير موجودة',
    'trusted_contact_updated'      => 'تم تحديث جهة الاتصال الموثوقة بنجاح',
    'trusted_contact_deleted'      => 'تم حذف جهة الاتصال الموثوقة بنجاح',

    // ── Favorite locations ────────────────────────────────────────────────────
    'location_not_found'           => 'الموقع غير موجود',

    // ── Chat ──────────────────────────────────────────────────────────────────
    'support_started'              => 'تم بدء محادثة الدعم',
    'trip_support_started'         => 'تم بدء محادثة دعم الرحلة',
    'trip_not_active'              => 'الرحلة غير نشطة',
    'conversation_closed'          => 'المحادثة مغلقة',

    // ── Wallet / Withdrawal ───────────────────────────────────────────────────
    'withdrawal_pending_exists'    => 'لديك طلب سحب معلّق بالفعل.',
    'withdrawal_submitted'         => 'تم تقديم طلب السحب بنجاح.',
    'insufficient_balance'         => 'رصيد المحفظة غير كافٍ. لا يمكن معالجة السحب.',
    'withdrawal_approved'          => 'تمت الموافقة على السحب. جارٍ معالجة الدفع.',
    'withdrawal_rejected'          => 'تم رفض طلب السحب.',
    'withdrawal_cannot_approve'    => "لا يمكن الموافقة على طلب بحالة ':status'.",
    'withdrawal_cannot_reject'     => "لا يمكن رفض طلب بحالة ':status'.",

    // ── Payout accounts ───────────────────────────────────────────────────────
    'payout_account_saved'         => 'تم حفظ حساب الدفع.',
    'payout_account_updated'       => 'تم تحديث حساب الدفع.',
    'payout_account_not_found'     => 'حساب الدفع غير موجود.',
    'payout_account_deleted'       => 'تم حذف حساب الدفع.',

    // ── Roles ─────────────────────────────────────────────────────────────────
    'invalid_permission_ids'       => 'معرّفات الصلاحيات غير صحيحة',
    'role_not_found'               => 'الدور غير موجود',
    'role_deleted'                 => 'تم حذف الدور',
    'role_restored'                => 'تم استعادة الدور',

    // ── Admin management ──────────────────────────────────────────────────────
    'admin_created'                => 'تم إنشاء المسؤول',
    'admin_create_failed'          => 'فشل إنشاء المسؤول',
    'admin_not_found'              => 'المسؤول غير موجود',
    'admin_updated'                => 'تم تحديث المسؤول',
    'admin_update_failed'          => 'فشل تحديث المسؤول',
    'permissions_updated'          => 'تم تحديث الصلاحيات',
];
