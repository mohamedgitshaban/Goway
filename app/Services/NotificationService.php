<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        protected FirebaseService $firebase
    ) {}

    /**
     * Send notification to a user: store in DB + send FCM push.
     */
    public function send(User $user, string $type, string $title, string $body, array $data = []): Notification
    {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        if ($user->fcm_token) {
            $this->firebase->sendToToken(
                $user->fcm_token,
                $title,
                $body,
                array_merge($data, ['type' => $type, 'notification_id' => (string) $notification->id])
            );
        }

        return $notification;
    }

    /**
     * Send notification to multiple users.
     */
    public function sendToMany($users, string $type, string $title, string $body, array $data = []): void
    {
        foreach ($users as $user) {
            $this->send($user, $type, $title, $body, $data);
        }
    }

    // ─── Trip Cycle Notifications ────────────────────────────────

    /**
     * New trip request → notify driver.
     */
    public function notifyNewTripRequest($trip, User $driver): void
    {
        $this->send($driver, 'trip_new_request', 'New Trip Request', 'You have a new trip request from ' . $trip->client->first_name, [
            'trip_id' => (string) $trip->id,
        ]);
    }

    /**
     * Trip accepted by driver → notify client.
     */
    public function notifyTripAccepted($trip): void
    {
        $this->send($trip->client, 'trip_accepted', 'Trip Accepted', 'Your trip has been accepted by a driver.', [
            'trip_id' => (string) $trip->id,
            'driver_id' => (string) $trip->driver_id,
        ]);
    }

    /**
     * Driver arrived at pickup → notify client.
     */
    public function notifyDriverArrived($trip): void
    {
        $this->send($trip->client, 'driver_arrived', 'Driver Arrived', 'Your driver has arrived at the pickup location.', [
            'trip_id' => (string) $trip->id,
        ]);
    }

    /**
     * Trip started → notify client.
     */
    public function notifyTripStarted($trip): void
    {
        $this->send($trip->client, 'trip_started', 'Trip Started', 'Your trip has started. Enjoy your ride!', [
            'trip_id' => (string) $trip->id,
        ]);
    }

    /**
     * Trip completed → notify both client and driver.
     */
    public function notifyTripCompleted($trip): void
    {
        $this->send($trip->client, 'trip_completed', 'Trip Completed', 'Your trip has been completed. Thank you for riding!', [
            'trip_id' => (string) $trip->id,
            'final_price' => (string) $trip->final_price,
        ]);

        $this->send($trip->driver, 'trip_completed', 'Trip Completed', 'Trip completed successfully.', [
            'trip_id' => (string) $trip->id,
            'final_price' => (string) $trip->final_price,
        ]);
    }

    /**
     * Trip cancelled → notify the other party.
     */
    public function notifyTripCancelled($trip, string $cancelledBy): void
    {
        if ($cancelledBy === 'client' && $trip->driver) {
            $this->send($trip->driver, 'trip_cancelled', 'Trip Cancelled', 'The client has cancelled the trip.', [
                'trip_id' => (string) $trip->id,
            ]);
        } elseif ($cancelledBy === 'driver') {
            $this->send($trip->client, 'trip_cancelled', 'Trip Cancelled', 'The driver has cancelled the trip.', [
                'trip_id' => (string) $trip->id,
            ]);
        }
    }

    /**
     * Driver assigned to trip → notify client.
     */
    public function notifyDriverAssigned($trip): void
    {
        $this->send($trip->client, 'driver_assigned', 'Driver Assigned', 'A driver has been assigned to your trip.', [
            'trip_id' => (string) $trip->id,
            'driver_id' => (string) $trip->driver_id,
        ]);
    }

    // ─── Negotiation Notifications ───────────────────────────────

    public function notifyNegotiationOffer($trip): void
    {
        $this->send($trip->client, 'negotiation_offer', 'Price Negotiation', 'The driver has sent a price negotiation for your trip.', [
            'trip_id' => (string) $trip->id,
        ]);
    }

    public function notifyNegotiationCounter($trip): void
    {
        if ($trip->driver) {
            $this->send($trip->driver, 'negotiation_counter', 'Counter Offer', 'The client has sent a counter offer for the trip.', [
                'trip_id' => (string) $trip->id,
            ]);
        }
    }

    public function notifyNegotiationAccepted($trip, string $acceptedBy): void
    {
        $target = $acceptedBy === 'client' ? $trip->driver : $trip->client;
        if ($target) {
            $this->send($target, 'negotiation_accepted', 'Negotiation Accepted', 'The negotiation offer has been accepted.', [
                'trip_id' => (string) $trip->id,
            ]);
        }
    }

    public function notifyNegotiationRejected($trip, string $rejectedBy): void
    {
        $target = $rejectedBy === 'client' ? $trip->driver : $trip->client;
        if ($target) {
            $this->send($target, 'negotiation_rejected', 'Negotiation Rejected', 'The negotiation offer has been rejected.', [
                'trip_id' => (string) $trip->id,
            ]);
        }
    }

    // ─── Offer & Coupon Notifications ────────────────────────────

    /**
     * Notify users about a new offer based on offer's user_type.
     */
    public function notifyNewOffer($offer, $users): void
    {
        foreach ($users as $user) {
            $this->send($user, 'offer_new', 'New Offer Available!', $offer->{'description_en'} ?? 'Check out our latest offer!', [
                'offer_id' => (string) $offer->id,
                'discount_type' => $offer->discount_type,
                'discount_value' => (string) $offer->discount_value,
            ]);
        }
    }

    /**
     * Notify clients about a new coupon.
     */
    public function notifyNewCoupon($coupon, $clients): void
    {
        foreach ($clients as $client) {
            $this->send($client, 'coupon_new', 'New Coupon Available!', 'Use code "' . $coupon->code . '" to get a discount!', [
                'coupon_id' => (string) $coupon->id,
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => (string) $coupon->discount_value,
            ]);
        }
    }
}
