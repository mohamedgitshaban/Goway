<?php

return [
    // ── Coupons ───────────────────────────────────────────────────────────────
    'coupon_not_found_or_inactive' => 'Coupon not found or inactive',
    'coupon_not_valid'             => 'Coupon is not valid for this trip',
    'coupon_valid'                 => 'Coupon applied successfully',

    // ── Auth – shared ─────────────────────────────────────────────────────────
    'user_not_found'               => 'User not found',
    'account_deactivated'          => 'Your account is deactivated.',
    'otp_sent_to_phone'            => 'OTP sent to phone',
    'otp_sent_to_email'            => 'OTP sent to email',
    'invalid_or_expired_otp'       => 'Invalid or expired OTP',
    'invalid_credentials'          => 'Invalid credentials',
    'logged_out'                   => 'Logged out',
    'profile_updated'              => 'Profile updated successfully',
    'account_deleted'              => 'Account deleted successfully',
    'password_reset_successful'    => 'Password reset successful',

    // ── Auth – driver ─────────────────────────────────────────────────────────
    'phone_registered_as_client'   => 'This phone is registered as a client account.',
    'phone_used_by_client'         => 'This phone is already used by a client account.',
    'registration_successful'      => 'Registration successful, OTP sent to phone',
    'driver_online'                => 'Driver is now online',
    'driver_offline'               => 'Driver is now offline',

    // ── Auth – client ─────────────────────────────────────────────────────────
    'phone_registered_as_driver'   => 'This phone is registered as a driver account.',
    'phone_used_by_driver'         => 'This phone is already used by a driver account.',

    // ── General ───────────────────────────────────────────────────────────────
    'unauthorized'                 => 'Unauthorized',
    'not_your_trip'                => 'Not your trip',
    'created_successfully'         => 'Created successfully',
    'updated_successfully'         => 'Updated successfully',
    'deleted_successfully'         => 'Deleted successfully',

    // ── Trips ─────────────────────────────────────────────────────────────────
    'trip_created'                 => 'Trip created successfully',
    'trip_not_ready_for_arrival'   => 'Trip is not ready for arrival',
    'driver_arrived'               => 'Driver marked as arrived',
    'trip_cannot_start'            => 'Trip cannot be started at this stage',
    'trip_started'                 => 'Trip started successfully',
    'trip_cannot_complete'         => 'Trip cannot be completed at this stage',
    'trip_completed'               => 'Trip completed successfully',
    'trip_cannot_cancel'           => 'Trip cannot be cancelled at this stage',
    'cannot_negotiate'             => 'Cannot negotiate at this stage',
    'offer_sent'                   => 'Offer sent to client',
    'trip_not_paid'                => 'Trip not paid yet',
    'client_rated'                 => 'Client rated successfully',
    'cannot_accept_offer'          => 'Cannot accept offer at this stage',
    'offer_accepted'               => 'Offer accepted',
    'offer_rejected'               => 'Offer rejected',
    'counter_offer_sent'           => 'Counter offer sent',
    'no_driver_assigned'           => 'No driver assigned',
    'driver_rated'                 => 'Driver rated successfully',

    // ── Trip types (BaseController) ───────────────────────────────────────────
    'trip_type_not_found'          => 'Trip type not found',
    'trip_type_restored'           => 'Trip type restored',
    'restore_not_supported'        => 'Restore not supported for this model',

    // ── Driver location ───────────────────────────────────────────────────────
    'not_a_driver'                 => 'Not a driver',
    'driver_not_active'            => 'Driver not active',
    'driver_offline_location'      => 'Driver is offline. Please go online to update location.',
    'location_unchanged'           => 'Location unchanged — no event broadcasted',

    // ── Vehicles ──────────────────────────────────────────────────────────────
    'vehicle_not_found'            => 'Vehicle not found',
    'vehicle_accepted'             => 'Vehicle accepted successfully',
    'vehicle_rejected'             => 'Vehicle rejected',

    // ── Driver documents ──────────────────────────────────────────────────────
    'driver_not_found'             => 'Driver not found',
    'documents_submitted'          => 'Documents submitted successfully and are under review',
    'document_not_found'           => 'Document not found',
    'documents_accepted'           => 'Documents accepted',
    'documents_rejected'           => 'Documents rejected',

    // ── Notifications ─────────────────────────────────────────────────────────
    'notification_marked_read'     => 'Notification marked as read',
    'all_notifications_read'       => 'All notifications marked as read',
    'fcm_token_updated'            => 'FCM token updated successfully',

    // ── Safety access ─────────────────────────────────────────────────────────
    'safety_access_updated'        => 'Safety access updated successfully',
    'safety_location_disabled'     => 'Safety location access is not enabled for your account',
    'trip_not_active_safety'       => 'Trip is not active for live safety tracking',
    'safety_location_stored'       => 'Safety location stored',
    'safety_voice_disabled'        => 'Safety voice access is not enabled for your account',
    'trip_not_active_voice'        => 'Trip is not active for voice safety recording',
    'voice_session_started'        => 'Voice safety session started',
    'trip_not_active_chunk'        => 'Trip is not active for chunk recording',
    'voice_chunk_stored'           => 'Voice chunk stored',
    'recording_not_yours'          => 'Recording does not belong to this trip/user',
    'voice_session_finished'       => 'Voice safety session finished',

    // ── Trusted contacts ──────────────────────────────────────────────────────
    'trusted_contacts_limit'       => 'You can only have up to :max trusted contacts.',
    'trusted_contact_added'        => 'Trusted contact added successfully',
    'trusted_contact_not_found'    => 'Trusted contact not found',
    'trusted_contact_updated'      => 'Trusted contact updated successfully',
    'trusted_contact_deleted'      => 'Trusted contact deleted successfully',

    // ── Favorite locations ────────────────────────────────────────────────────
    'location_not_found'           => 'Location not found',

    // ── Chat ──────────────────────────────────────────────────────────────────
    'support_started'              => 'Support conversation started',
    'trip_support_started'         => 'Trip support conversation started',
    'trip_not_active'              => 'Trip is not active',
    'conversation_closed'          => 'Conversation is closed',

    // ── Wallet / Withdrawal ───────────────────────────────────────────────────
    'withdrawal_pending_exists'    => 'You already have a pending withdrawal request.',
    'withdrawal_submitted'         => 'Withdrawal request submitted successfully.',
    'insufficient_balance'         => 'Insufficient wallet balance. Cannot process withdrawal.',
    'withdrawal_approved'          => 'Withdrawal approved. Payout is being processed.',
    'withdrawal_rejected'          => 'Withdrawal request rejected.',
    'withdrawal_cannot_approve'    => "Cannot approve a request with status ':status'.",
    'withdrawal_cannot_reject'     => "Cannot reject a request with status ':status'.",

    // ── Payout accounts ───────────────────────────────────────────────────────
    'payout_account_saved'         => 'Payout account saved.',
    'payout_account_updated'       => 'Payout account updated.',
    'payout_account_not_found'     => 'Payout account not found.',
    'payout_account_deleted'       => 'Payout account deleted.',

    // ── Roles ─────────────────────────────────────────────────────────────────
    'invalid_permission_ids'       => 'Invalid permission ids',
    'role_not_found'               => 'Role not found',
    'role_deleted'                 => 'Role deleted',
    'role_restored'                => 'Role restored',

    // ── Admin management ──────────────────────────────────────────────────────
    'admin_created'                => 'Admin created',
    'admin_create_failed'          => 'Failed to create admin',
    'admin_not_found'              => 'Admin not found',
    'admin_updated'                => 'Admin updated',
    'admin_update_failed'          => 'Failed to update admin',
    'permissions_updated'          => 'Permissions updated',
];
