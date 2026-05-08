<?php

namespace App\VoltTests;

use VoltTest\Laravel\Contracts\VoltTestCase;
use VoltTest\Laravel\VoltTestManager;

class UserTest implements VoltTestCase
{
    /**
     * Define the test scenario.
     *
     * @param VoltTestManager $manager
     * @return void
     */
    public function define(VoltTestManager $manager): void
    {
        // Define your test scenario
        $scenario = $manager->scenario('UserTest');

        // Step 1 : Sanctum.csrfCookie
        $scenario->step('Sanctum.csrfCookie')
            ->get('/sanctum/csrf-cookie', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 2 : TelescopeTelescopeApiMail
        $scenario->step('TelescopeTelescopeApiMail')
            ->post('/telescope/telescope-api/mail', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 3 : TelescopeTelescopeApiMailTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiMailTelescopeEntryId')
            ->get('/telescope/telescope-api/mail/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 4 : TelescopeTelescopeApiMailTelescopeEntryIdPreview
        $scenario->step('TelescopeTelescopeApiMailTelescopeEntryIdPreview')
            ->get('/telescope/telescope-api/mail/${telescopeEntryId}/preview', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 5 : TelescopeTelescopeApiMailTelescopeEntryIdDownload
        $scenario->step('TelescopeTelescopeApiMailTelescopeEntryIdDownload')
            ->get('/telescope/telescope-api/mail/${telescopeEntryId}/download', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 6 : TelescopeTelescopeApiExceptions
        $scenario->step('TelescopeTelescopeApiExceptions')
            ->post('/telescope/telescope-api/exceptions', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 7 : TelescopeTelescopeApiExceptionsTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiExceptionsTelescopeEntryId')
            ->get('/telescope/telescope-api/exceptions/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 8 : TelescopeTelescopeApiExceptionsTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiExceptionsTelescopeEntryId')
            ->put('/telescope/telescope-api/exceptions/${telescopeEntryId}', [
            '_token' => '${csrf_token}',
            '_method' => 'put',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 9 : TelescopeTelescopeApiDumps
        $scenario->step('TelescopeTelescopeApiDumps')
            ->post('/telescope/telescope-api/dumps', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 10 : TelescopeTelescopeApiLogs
        $scenario->step('TelescopeTelescopeApiLogs')
            ->post('/telescope/telescope-api/logs', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 11 : TelescopeTelescopeApiLogsTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiLogsTelescopeEntryId')
            ->get('/telescope/telescope-api/logs/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 12 : TelescopeTelescopeApiNotifications
        $scenario->step('TelescopeTelescopeApiNotifications')
            ->post('/telescope/telescope-api/notifications', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 13 : TelescopeTelescopeApiNotificationsTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiNotificationsTelescopeEntryId')
            ->get('/telescope/telescope-api/notifications/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 14 : TelescopeTelescopeApiJobs
        $scenario->step('TelescopeTelescopeApiJobs')
            ->post('/telescope/telescope-api/jobs', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 15 : TelescopeTelescopeApiJobsTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiJobsTelescopeEntryId')
            ->get('/telescope/telescope-api/jobs/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 16 : TelescopeTelescopeApiBatches
        $scenario->step('TelescopeTelescopeApiBatches')
            ->post('/telescope/telescope-api/batches', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 17 : TelescopeTelescopeApiBatchesTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiBatchesTelescopeEntryId')
            ->get('/telescope/telescope-api/batches/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 18 : TelescopeTelescopeApiEvents
        $scenario->step('TelescopeTelescopeApiEvents')
            ->post('/telescope/telescope-api/events', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 19 : TelescopeTelescopeApiEventsTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiEventsTelescopeEntryId')
            ->get('/telescope/telescope-api/events/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 20 : TelescopeTelescopeApiGates
        $scenario->step('TelescopeTelescopeApiGates')
            ->post('/telescope/telescope-api/gates', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 21 : TelescopeTelescopeApiGatesTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiGatesTelescopeEntryId')
            ->get('/telescope/telescope-api/gates/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 22 : TelescopeTelescopeApiCache
        $scenario->step('TelescopeTelescopeApiCache')
            ->post('/telescope/telescope-api/cache', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 23 : TelescopeTelescopeApiCacheTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiCacheTelescopeEntryId')
            ->get('/telescope/telescope-api/cache/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 24 : TelescopeTelescopeApiQueries
        $scenario->step('TelescopeTelescopeApiQueries')
            ->post('/telescope/telescope-api/queries', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 25 : TelescopeTelescopeApiQueriesTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiQueriesTelescopeEntryId')
            ->get('/telescope/telescope-api/queries/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 26 : TelescopeTelescopeApiModels
        $scenario->step('TelescopeTelescopeApiModels')
            ->post('/telescope/telescope-api/models', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 27 : TelescopeTelescopeApiModelsTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiModelsTelescopeEntryId')
            ->get('/telescope/telescope-api/models/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 28 : TelescopeTelescopeApiRequests
        $scenario->step('TelescopeTelescopeApiRequests')
            ->post('/telescope/telescope-api/requests', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 29 : TelescopeTelescopeApiRequestsTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiRequestsTelescopeEntryId')
            ->get('/telescope/telescope-api/requests/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 30 : TelescopeTelescopeApiViews
        $scenario->step('TelescopeTelescopeApiViews')
            ->post('/telescope/telescope-api/views', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 31 : TelescopeTelescopeApiViewsTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiViewsTelescopeEntryId')
            ->get('/telescope/telescope-api/views/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 32 : TelescopeTelescopeApiCommands
        $scenario->step('TelescopeTelescopeApiCommands')
            ->post('/telescope/telescope-api/commands', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 33 : TelescopeTelescopeApiCommandsTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiCommandsTelescopeEntryId')
            ->get('/telescope/telescope-api/commands/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 34 : TelescopeTelescopeApiSchedule
        $scenario->step('TelescopeTelescopeApiSchedule')
            ->post('/telescope/telescope-api/schedule', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 35 : TelescopeTelescopeApiScheduleTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiScheduleTelescopeEntryId')
            ->get('/telescope/telescope-api/schedule/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 36 : TelescopeTelescopeApiRedis
        $scenario->step('TelescopeTelescopeApiRedis')
            ->post('/telescope/telescope-api/redis', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 37 : TelescopeTelescopeApiRedisTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiRedisTelescopeEntryId')
            ->get('/telescope/telescope-api/redis/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 38 : TelescopeTelescopeApiClientRequests
        $scenario->step('TelescopeTelescopeApiClientRequests')
            ->post('/telescope/telescope-api/client-requests', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 39 : TelescopeTelescopeApiClientRequestsTelescopeEntryId
        $scenario->step('TelescopeTelescopeApiClientRequestsTelescopeEntryId')
            ->get('/telescope/telescope-api/client-requests/${telescopeEntryId}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 40 : TelescopeTelescopeApiMonitoredTags
        $scenario->step('TelescopeTelescopeApiMonitoredTags')
            ->get('/telescope/telescope-api/monitored-tags', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 41 : TelescopeTelescopeApiMonitoredTags
        $scenario->step('TelescopeTelescopeApiMonitoredTags')
            ->post('/telescope/telescope-api/monitored-tags', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 42 : TelescopeTelescopeApiMonitoredTagsDelete
        $scenario->step('TelescopeTelescopeApiMonitoredTagsDelete')
            ->post('/telescope/telescope-api/monitored-tags/delete', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 43 : TelescopeTelescopeApiToggleRecording
        $scenario->step('TelescopeTelescopeApiToggleRecording')
            ->post('/telescope/telescope-api/toggle-recording', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 44 : TelescopeTelescopeApiEntries
        $scenario->step('TelescopeTelescopeApiEntries')
            ->delete('/telescope/telescope-api/entries', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 45 : Telescope
        $scenario->step('Telescope')
            ->get('/telescope/${view}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 46 : Ignition.healthCheck
        $scenario->step('Ignition.healthCheck')
            ->get('/_ignition/health-check', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 47 : Ignition.executeSolution
        $scenario->step('Ignition.executeSolution')
            ->post('/_ignition/execute-solution', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 48 : Ignition.updateConfig
        $scenario->step('Ignition.updateConfig')
            ->post('/_ignition/update-config', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 49 : ApiDriverAuthLogin
        $scenario->step('ApiDriverAuthLogin')
            ->post('/api/driver/auth/login', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 50 : ApiDriverAuthSendOtp
        $scenario->step('ApiDriverAuthSendOtp')
            ->post('/api/driver/auth/send_otp', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 51 : ApiDriverAuthRegister
        $scenario->step('ApiDriverAuthRegister')
            ->post('/api/driver/auth/register', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 52 : ApiDriverAuthActivatePhone
        $scenario->step('ApiDriverAuthActivatePhone')
            ->post('/api/driver/auth/activate_phone', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 53 : ApiDriverAuthForgotPassword
        $scenario->step('ApiDriverAuthForgotPassword')
            ->post('/api/driver/auth/forgot-password', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 54 : ApiDriverAuthResetPassword
        $scenario->step('ApiDriverAuthResetPassword')
            ->post('/api/driver/auth/reset-password', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 55 : ApiClientAuthLogin
        $scenario->step('ApiClientAuthLogin')
            ->post('/api/client/auth/login', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 56 : ApiClientAuthSendOtp
        $scenario->step('ApiClientAuthSendOtp')
            ->post('/api/client/auth/send_otp', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 57 : ApiClientAuthRegister
        $scenario->step('ApiClientAuthRegister')
            ->post('/api/client/auth/register', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 58 : ApiClientAuthActivatePhone
        $scenario->step('ApiClientAuthActivatePhone')
            ->post('/api/client/auth/activate_phone', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 59 : ApiClientAuthForgotPassword
        $scenario->step('ApiClientAuthForgotPassword')
            ->post('/api/client/auth/forgot-password', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 60 : ApiClientAuthResetPassword
        $scenario->step('ApiClientAuthResetPassword')
            ->post('/api/client/auth/reset-password', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 61 : ApiAdminAuthLogin
        $scenario->step('ApiAdminAuthLogin')
            ->post('/api/admin/auth/login', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 62 : ApiAdminAuthForgotPassword
        $scenario->step('ApiAdminAuthForgotPassword')
            ->post('/api/admin/auth/forgot-password', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 63 : ApiAdminAuthResetPassword
        $scenario->step('ApiAdminAuthResetPassword')
            ->post('/api/admin/auth/reset-password', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 64 : ApiDriverAuthLogout
        $scenario->step('ApiDriverAuthLogout')
            ->post('/api/driver/auth/logout', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 65 : ApiDriverAuthProfile
        $scenario->step('ApiDriverAuthProfile')
            ->get('/api/driver/auth/profile', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 66 : ApiDriverAuthProfile
        $scenario->step('ApiDriverAuthProfile')
            ->put('/api/driver/auth/profile', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 67 : ApiDriverAuthProfile
        $scenario->step('ApiDriverAuthProfile')
            ->delete('/api/driver/auth/profile', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 68 : ApiDriverAuthGoOnline
        $scenario->step('ApiDriverAuthGoOnline')
            ->put('/api/driver/auth/goOnline', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 69 : ApiDriverAuthGoOffline
        $scenario->step('ApiDriverAuthGoOffline')
            ->put('/api/driver/auth/goOffline', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 70 : ApiDriverAuthToggleonlinestatus
        $scenario->step('ApiDriverAuthToggleonlinestatus')
            ->put('/api/driver/auth/toggleonlinestatus', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 71 : ApiDriverOffers
        $scenario->step('ApiDriverOffers')
            ->get('/api/driver/offers', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 72 : ApiDriverVehicleIdBrands
        $scenario->step('ApiDriverVehicleIdBrands')
            ->get('/api/driver/vehicle/${id}/brands', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 73 : ApiDriverVehicleModels
        $scenario->step('ApiDriverVehicleModels')
            ->get('/api/driver/vehicle/models', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 74 : ApiDriverDocumentsTripTypes
        $scenario->step('ApiDriverDocumentsTripTypes')
            ->get('/api/driver/documents/trip_types', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 75 : ApiDriverDocumentsUpload
        $scenario->step('ApiDriverDocumentsUpload')
            ->post('/api/driver/documents/upload', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 76 : ApiDriverDocuments
        $scenario->step('ApiDriverDocuments')
            ->get('/api/driver/documents', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 77 : ApiDriverLocation
        $scenario->step('ApiDriverLocation')
            ->post('/api/driver/location', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 78 : ApiDriverTripsStatsCompletedAverage7Days
        $scenario->step('ApiDriverTripsStatsCompletedAverage7Days')
            ->get('/api/driver/trips/stats/completed-average-7-days', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 79 : ApiDriverTripsTripAccept
        $scenario->step('ApiDriverTripsTripAccept')
            ->post('/api/driver/trips/${trip}/accept', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 80 : ApiDriverTripsTripArrived
        $scenario->step('ApiDriverTripsTripArrived')
            ->post('/api/driver/trips/${trip}/arrived', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 81 : ApiDriverTripsTripStart
        $scenario->step('ApiDriverTripsTripStart')
            ->post('/api/driver/trips/${trip}/start', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 82 : ApiDriverTripsTripComplete
        $scenario->step('ApiDriverTripsTripComplete')
            ->post('/api/driver/trips/${trip}/complete', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 83 : ApiDriverTripsTripCancel
        $scenario->step('ApiDriverTripsTripCancel')
            ->post('/api/driver/trips/${trip}/cancel', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 84 : ApiDriverTripsTripNegotiate
        $scenario->step('ApiDriverTripsTripNegotiate')
            ->post('/api/driver/trips/${trip}/negotiate', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 85 : ApiDriverTripsTripRate
        $scenario->step('ApiDriverTripsTripRate')
            ->post('/api/driver/trips/${trip}/rate', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 86 : ApiDriverTripsTripSafetyLocation
        $scenario->step('ApiDriverTripsTripSafetyLocation')
            ->post('/api/driver/trips/${trip}/safety/location', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 87 : ApiDriverTripsTripSafetyVoice
        $scenario->step('ApiDriverTripsTripSafetyVoice')
            ->post('/api/driver/trips/${trip}/safety/voice', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 88 : ApiDriverTripsTripSafetyVoiceStart
        $scenario->step('ApiDriverTripsTripSafetyVoiceStart')
            ->post('/api/driver/trips/${trip}/safety/voice/start', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 89 : ApiDriverTripsTripSafetyVoiceChunk
        $scenario->step('ApiDriverTripsTripSafetyVoiceChunk')
            ->post('/api/driver/trips/${trip}/safety/voice/chunk', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 90 : ApiDriverTripsTripSafetyVoiceRecordingFinish
        $scenario->step('ApiDriverTripsTripSafetyVoiceRecordingFinish')
            ->post('/api/driver/trips/${trip}/safety/voice/${recording}/finish', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 91 : ApiDriverTrips
        $scenario->step('ApiDriverTrips')
            ->get('/api/driver/trips', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 92 : ApiDriverVehicles
        $scenario->step('ApiDriverVehicles')
            ->get('/api/driver/vehicles', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 93 : ApiDriverVehicles
        $scenario->step('ApiDriverVehicles')
            ->post('/api/driver/vehicles', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 94 : ApiDriverVehiclesId
        $scenario->step('ApiDriverVehiclesId')
            ->put('/api/driver/vehicles/${id}', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 95 : ApiDriverVehiclesIdActivate
        $scenario->step('ApiDriverVehiclesIdActivate')
            ->post('/api/driver/vehicles/${id}/activate', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 96 : ApiDriverNotifications
        $scenario->step('ApiDriverNotifications')
            ->get('/api/driver/notifications', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 97 : ApiDriverNotificationsUnreadCount
        $scenario->step('ApiDriverNotificationsUnreadCount')
            ->get('/api/driver/notifications/unread-count', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 98 : ApiDriverNotificationsIdRead
        $scenario->step('ApiDriverNotificationsIdRead')
            ->post('/api/driver/notifications/${id}/read', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 99 : ApiDriverNotificationsReadAll
        $scenario->step('ApiDriverNotificationsReadAll')
            ->post('/api/driver/notifications/read-all', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 100 : ApiDriverFcmToken
        $scenario->step('ApiDriverFcmToken')
            ->post('/api/driver/fcm-token', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 101 : ApiDriverWalletTransactions
        $scenario->step('ApiDriverWalletTransactions')
            ->get('/api/driver/wallet/transactions', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 102 : ApiDriverSafetyAccess
        $scenario->step('ApiDriverSafetyAccess')
            ->get('/api/driver/safety-access', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 103 : ApiDriverSafetyAccess
        $scenario->step('ApiDriverSafetyAccess')
            ->put('/api/driver/safety-access', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 104 : ApiDriverTrustedContacts
        $scenario->step('ApiDriverTrustedContacts')
            ->get('/api/driver/trusted-contacts', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 105 : ApiDriverTrustedContacts
        $scenario->step('ApiDriverTrustedContacts')
            ->post('/api/driver/trusted-contacts', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 106 : ApiDriverTrustedContactsId
        $scenario->step('ApiDriverTrustedContactsId')
            ->put('/api/driver/trusted-contacts/${id}', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 107 : ApiDriverTrustedContactsId
        $scenario->step('ApiDriverTrustedContactsId')
            ->delete('/api/driver/trusted-contacts/${id}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 108 : ApiDriverChatConversations
        $scenario->step('ApiDriverChatConversations')
            ->get('/api/driver/chat/conversations', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 109 : ApiDriverChatSupport
        $scenario->step('ApiDriverChatSupport')
            ->post('/api/driver/chat/support', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 110 : ApiDriverChatTripTripSupport
        $scenario->step('ApiDriverChatTripTripSupport')
            ->post('/api/driver/chat/trip/${trip}/support', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 111 : ApiDriverChatTripTripStart
        $scenario->step('ApiDriverChatTripTripStart')
            ->post('/api/driver/chat/trip/${trip}/start', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 112 : ApiDriverChatConversationMessages
        $scenario->step('ApiDriverChatConversationMessages')
            ->get('/api/driver/chat/${conversation}/messages', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 113 : ApiDriverChatConversationMessages
        $scenario->step('ApiDriverChatConversationMessages')
            ->post('/api/driver/chat/${conversation}/messages', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 114 : ApiClientAuthLogout
        $scenario->step('ApiClientAuthLogout')
            ->post('/api/client/auth/logout', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 115 : ApiClientAuthProfile
        $scenario->step('ApiClientAuthProfile')
            ->get('/api/client/auth/profile', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 116 : ApiClientAuthProfile
        $scenario->step('ApiClientAuthProfile')
            ->put('/api/client/auth/profile', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 117 : ApiClientAuthProfile
        $scenario->step('ApiClientAuthProfile')
            ->delete('/api/client/auth/profile', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 118 : FavoriteLocations.index
        $scenario->step('FavoriteLocations.index')
            ->get('/api/client/favorite-locations', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 119 : FavoriteLocations.store
        $scenario->step('FavoriteLocations.store')
            ->post('/api/client/favorite-locations', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 120 : FavoriteLocations.show
        $scenario->step('FavoriteLocations.show')
            ->get('/api/client/favorite-locations/${favorite_location}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 121 : FavoriteLocations.update
        $scenario->step('FavoriteLocations.update')
            ->put('/api/client/favorite-locations/${favorite_location}', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 122 : FavoriteLocations.destroy
        $scenario->step('FavoriteLocations.destroy')
            ->delete('/api/client/favorite-locations/${favorite_location}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 123 : ApiClientOffers
        $scenario->step('ApiClientOffers')
            ->get('/api/client/offers', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 124 : ApiClientCoupons
        $scenario->step('ApiClientCoupons')
            ->get('/api/client/coupons', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 125 : ApiClientNearbyDrivers
        $scenario->step('ApiClientNearbyDrivers')
            ->get('/api/client/nearby-drivers', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 126 : ApiClientTripsEstimate
        $scenario->step('ApiClientTripsEstimate')
            ->post('/api/client/trips/estimate', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 127 : ApiClientTripsApplyCoupon
        $scenario->step('ApiClientTripsApplyCoupon')
            ->post('/api/client/trips/apply_coupon', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 128 : ApiClientTrips
        $scenario->step('ApiClientTrips')
            ->post('/api/client/trips', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 129 : ApiClientTripsTripCancel
        $scenario->step('ApiClientTripsTripCancel')
            ->post('/api/client/trips/${trip}/cancel', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 130 : ApiClientTripsTripNegotiateAccept
        $scenario->step('ApiClientTripsTripNegotiateAccept')
            ->post('/api/client/trips/${trip}/negotiate/accept', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 131 : ApiClientTripsTripNegotiateReject
        $scenario->step('ApiClientTripsTripNegotiateReject')
            ->post('/api/client/trips/${trip}/negotiate/reject', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 132 : ApiClientTripsTripNegotiateCounter
        $scenario->step('ApiClientTripsTripNegotiateCounter')
            ->post('/api/client/trips/${trip}/negotiate/counter', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 133 : ApiClientTripsTripRate
        $scenario->step('ApiClientTripsTripRate')
            ->post('/api/client/trips/${trip}/rate', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 134 : ApiClientTripsTripSafetyLocation
        $scenario->step('ApiClientTripsTripSafetyLocation')
            ->post('/api/client/trips/${trip}/safety/location', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 135 : ApiClientTripsTripSafetyVoice
        $scenario->step('ApiClientTripsTripSafetyVoice')
            ->post('/api/client/trips/${trip}/safety/voice', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 136 : ApiClientTripsTripSafetyVoiceStart
        $scenario->step('ApiClientTripsTripSafetyVoiceStart')
            ->post('/api/client/trips/${trip}/safety/voice/start', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 137 : ApiClientTripsTripSafetyVoiceChunk
        $scenario->step('ApiClientTripsTripSafetyVoiceChunk')
            ->post('/api/client/trips/${trip}/safety/voice/chunk', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 138 : ApiClientTripsTripSafetyVoiceRecordingFinish
        $scenario->step('ApiClientTripsTripSafetyVoiceRecordingFinish')
            ->post('/api/client/trips/${trip}/safety/voice/${recording}/finish', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 139 : ApiClientTrips
        $scenario->step('ApiClientTrips')
            ->get('/api/client/trips', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 140 : ApiClientNotifications
        $scenario->step('ApiClientNotifications')
            ->get('/api/client/notifications', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 141 : ApiClientNotificationsUnreadCount
        $scenario->step('ApiClientNotificationsUnreadCount')
            ->get('/api/client/notifications/unread-count', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 142 : ApiClientNotificationsIdRead
        $scenario->step('ApiClientNotificationsIdRead')
            ->post('/api/client/notifications/${id}/read', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 143 : ApiClientNotificationsReadAll
        $scenario->step('ApiClientNotificationsReadAll')
            ->post('/api/client/notifications/read-all', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 144 : ApiClientFcmToken
        $scenario->step('ApiClientFcmToken')
            ->post('/api/client/fcm-token', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 145 : ApiClientWalletBalance
        $scenario->step('ApiClientWalletBalance')
            ->get('/api/client/wallet/balance', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 146 : ApiClientWalletTransactions
        $scenario->step('ApiClientWalletTransactions')
            ->get('/api/client/wallet/transactions', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 147 : ApiClientSafetyAccess
        $scenario->step('ApiClientSafetyAccess')
            ->get('/api/client/safety-access', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 148 : ApiClientSafetyAccess
        $scenario->step('ApiClientSafetyAccess')
            ->put('/api/client/safety-access', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 149 : TrustedContacts.index
        $scenario->step('TrustedContacts.index')
            ->get('/api/client/trusted-contacts', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 150 : TrustedContacts.store
        $scenario->step('TrustedContacts.store')
            ->post('/api/client/trusted-contacts', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 151 : TrustedContacts.show
        $scenario->step('TrustedContacts.show')
            ->get('/api/client/trusted-contacts/${trusted_contact}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 152 : TrustedContacts.update
        $scenario->step('TrustedContacts.update')
            ->put('/api/client/trusted-contacts/${trusted_contact}', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 153 : TrustedContacts.destroy
        $scenario->step('TrustedContacts.destroy')
            ->delete('/api/client/trusted-contacts/${trusted_contact}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 154 : ApiClientChatConversations
        $scenario->step('ApiClientChatConversations')
            ->get('/api/client/chat/conversations', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 155 : ApiClientChatSupport
        $scenario->step('ApiClientChatSupport')
            ->post('/api/client/chat/support', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 156 : ApiClientChatTripTripSupport
        $scenario->step('ApiClientChatTripTripSupport')
            ->post('/api/client/chat/trip/${trip}/support', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 157 : ApiClientChatTripTripStart
        $scenario->step('ApiClientChatTripTripStart')
            ->post('/api/client/chat/trip/${trip}/start', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 158 : ApiClientChatConversationMessages
        $scenario->step('ApiClientChatConversationMessages')
            ->get('/api/client/chat/${conversation}/messages', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 159 : ApiClientChatConversationMessages
        $scenario->step('ApiClientChatConversationMessages')
            ->post('/api/client/chat/${conversation}/messages', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 160 : ApiAdminAuthLogout
        $scenario->step('ApiAdminAuthLogout')
            ->post('/api/admin/auth/logout', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 161 : ApiAdminAuthProfile
        $scenario->step('ApiAdminAuthProfile')
            ->get('/api/admin/auth/profile', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 162 : ApiAdminAuthProfile
        $scenario->step('ApiAdminAuthProfile')
            ->put('/api/admin/auth/profile', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 163 : ApiAdminClients
        $scenario->step('ApiAdminClients')
            ->get('/api/admin/clients', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 164 : ApiAdminClientsExport
        $scenario->step('ApiAdminClientsExport')
            ->get('/api/admin/clients/export', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 165 : ApiAdminClientsId
        $scenario->step('ApiAdminClientsId')
            ->get('/api/admin/clients/${id}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 166 : ApiAdminClientsIdActivate
        $scenario->step('ApiAdminClientsIdActivate')
            ->put('/api/admin/clients/${id}/activate', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 167 : ApiAdminClientsIdSuspend
        $scenario->step('ApiAdminClientsIdSuspend')
            ->put('/api/admin/clients/${id}/suspend', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 168 : ApiAdminClientsIdStatusToggle
        $scenario->step('ApiAdminClientsIdStatusToggle')
            ->put('/api/admin/clients/${id}/status-toggle', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 169 : ApiAdminClientsId
        $scenario->step('ApiAdminClientsId')
            ->delete('/api/admin/clients/${id}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 170 : ApiAdminClientsIdRestore
        $scenario->step('ApiAdminClientsIdRestore')
            ->put('/api/admin/clients/${id}/restore', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 171 : ApiAdminDrivers
        $scenario->step('ApiAdminDrivers')
            ->get('/api/admin/drivers', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 172 : ApiAdminDriversExport
        $scenario->step('ApiAdminDriversExport')
            ->get('/api/admin/drivers/export', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 173 : ApiAdminDriversId
        $scenario->step('ApiAdminDriversId')
            ->get('/api/admin/drivers/${id}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 174 : ApiAdminDriversIdActivate
        $scenario->step('ApiAdminDriversIdActivate')
            ->put('/api/admin/drivers/${id}/activate', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 175 : ApiAdminDriversIdSuspend
        $scenario->step('ApiAdminDriversIdSuspend')
            ->put('/api/admin/drivers/${id}/suspend', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 176 : ApiAdminDriversIdStatusToggle
        $scenario->step('ApiAdminDriversIdStatusToggle')
            ->put('/api/admin/drivers/${id}/status-toggle', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 177 : ApiAdminDriversId
        $scenario->step('ApiAdminDriversId')
            ->delete('/api/admin/drivers/${id}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 178 : ApiAdminDriversIdRestore
        $scenario->step('ApiAdminDriversIdRestore')
            ->put('/api/admin/drivers/${id}/restore', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 179 : ApiAdminAdmins
        $scenario->step('ApiAdminAdmins')
            ->post('/api/admin/admins', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 180 : ApiAdminAdminsId
        $scenario->step('ApiAdminAdminsId')
            ->put('/api/admin/admins/${id}', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 181 : ApiAdminAdmins
        $scenario->step('ApiAdminAdmins')
            ->get('/api/admin/admins', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 182 : ApiAdminAdminsExport
        $scenario->step('ApiAdminAdminsExport')
            ->get('/api/admin/admins/export', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 183 : ApiAdminAdminsId
        $scenario->step('ApiAdminAdminsId')
            ->get('/api/admin/admins/${id}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 184 : ApiAdminAdminsIdActivate
        $scenario->step('ApiAdminAdminsIdActivate')
            ->put('/api/admin/admins/${id}/activate', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 185 : ApiAdminAdminsIdSuspend
        $scenario->step('ApiAdminAdminsIdSuspend')
            ->put('/api/admin/admins/${id}/suspend', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 186 : ApiAdminAdminsIdStatusToggle
        $scenario->step('ApiAdminAdminsIdStatusToggle')
            ->put('/api/admin/admins/${id}/status-toggle', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 187 : ApiAdminAdminsId
        $scenario->step('ApiAdminAdminsId')
            ->delete('/api/admin/admins/${id}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 188 : ApiAdminAdminsIdRestore
        $scenario->step('ApiAdminAdminsIdRestore')
            ->put('/api/admin/admins/${id}/restore', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 189 : ApiAdminRoles
        $scenario->step('ApiAdminRoles')
            ->get('/api/admin/roles', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 190 : ApiAdminRolesPermissionsAll
        $scenario->step('ApiAdminRolesPermissionsAll')
            ->get('/api/admin/roles/permissions/all', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 191 : ApiAdminRolesSelect
        $scenario->step('ApiAdminRolesSelect')
            ->get('/api/admin/roles/select', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 192 : ApiAdminRoles
        $scenario->step('ApiAdminRoles')
            ->post('/api/admin/roles', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 193 : ApiAdminRolesId
        $scenario->step('ApiAdminRolesId')
            ->get('/api/admin/roles/${id}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 194 : ApiAdminRolesId
        $scenario->step('ApiAdminRolesId')
            ->put('/api/admin/roles/${id}', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 195 : ApiAdminRolesId
        $scenario->step('ApiAdminRolesId')
            ->delete('/api/admin/roles/${id}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 196 : ApiAdminRolesIdRestore
        $scenario->step('ApiAdminRolesIdRestore')
            ->put('/api/admin/roles/${id}/restore', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 197 : ApiAdminAdminsIdPermissions
        $scenario->step('ApiAdminAdminsIdPermissions')
            ->get('/api/admin/admins/${id}/permissions', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 198 : ApiAdminTripTypes
        $scenario->step('ApiAdminTripTypes')
            ->get('/api/admin/trip_types', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 199 : ApiAdminTripTypesExport
        $scenario->step('ApiAdminTripTypesExport')
            ->get('/api/admin/trip_types/export', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 200 : ApiAdminTripTypes
        $scenario->step('ApiAdminTripTypes')
            ->post('/api/admin/trip_types', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 201 : ApiAdminTripTypesId
        $scenario->step('ApiAdminTripTypesId')
            ->put('/api/admin/trip_types/${id}', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 202 : ApiAdminTripTypesId
        $scenario->step('ApiAdminTripTypesId')
            ->get('/api/admin/trip_types/${id}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 203 : ApiAdminTripTypesIdActivate
        $scenario->step('ApiAdminTripTypesIdActivate')
            ->put('/api/admin/trip_types/${id}/activate', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 204 : ApiAdminTripTypesIdSuspend
        $scenario->step('ApiAdminTripTypesIdSuspend')
            ->put('/api/admin/trip_types/${id}/suspend', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 205 : ApiAdminTripTypesIdStatusToggle
        $scenario->step('ApiAdminTripTypesIdStatusToggle')
            ->put('/api/admin/trip_types/${id}/status-toggle', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 206 : ApiAdminTripTypesIdLicenceToggle
        $scenario->step('ApiAdminTripTypesIdLicenceToggle')
            ->put('/api/admin/trip_types/${id}/licence-toggle', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 207 : ApiAdminTripTypesId
        $scenario->step('ApiAdminTripTypesId')
            ->delete('/api/admin/trip_types/${id}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 208 : ApiAdminTripTypesIdRestore
        $scenario->step('ApiAdminTripTypesIdRestore')
            ->put('/api/admin/trip_types/${id}/restore', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 209 : ApiAdminWallets
        $scenario->step('ApiAdminWallets')
            ->get('/api/admin/wallets', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 210 : ApiAdminWalletsId
        $scenario->step('ApiAdminWalletsId')
            ->get('/api/admin/wallets/${id}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 211 : ApiAdminWalletTransactions
        $scenario->step('ApiAdminWalletTransactions')
            ->get('/api/admin/wallet-transactions', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 212 : ApiAdminDocuments
        $scenario->step('ApiAdminDocuments')
            ->get('/api/admin/documents', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 213 : ApiAdminDocumentsIdAccept
        $scenario->step('ApiAdminDocumentsIdAccept')
            ->post('/api/admin/documents/${id}/accept', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 214 : ApiAdminDocumentsIdReject
        $scenario->step('ApiAdminDocumentsIdReject')
            ->post('/api/admin/documents/${id}/reject', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 215 : ApiAdminVehicles
        $scenario->step('ApiAdminVehicles')
            ->get('/api/admin/vehicles', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 216 : ApiAdminVehiclesIdAccept
        $scenario->step('ApiAdminVehiclesIdAccept')
            ->post('/api/admin/vehicles/${id}/accept', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 217 : ApiAdminVehiclesIdReject
        $scenario->step('ApiAdminVehiclesIdReject')
            ->post('/api/admin/vehicles/${id}/reject', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 218 : ApiAdminDashboardStats
        $scenario->step('ApiAdminDashboardStats')
            ->get('/api/admin/dashboard/stats', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 219 : ApiAdminOffers
        $scenario->step('ApiAdminOffers')
            ->get('/api/admin/offers', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 220 : ApiAdminOffers
        $scenario->step('ApiAdminOffers')
            ->post('/api/admin/offers', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 221 : ApiAdminOffersOffer
        $scenario->step('ApiAdminOffersOffer')
            ->get('/api/admin/offers/${offer}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 222 : ApiAdminOffersOffer
        $scenario->step('ApiAdminOffersOffer')
            ->put('/api/admin/offers/${offer}', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 223 : ApiAdminOffersOffer
        $scenario->step('ApiAdminOffersOffer')
            ->delete('/api/admin/offers/${offer}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 224 : ApiAdminCoupons
        $scenario->step('ApiAdminCoupons')
            ->get('/api/admin/coupons', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 225 : ApiAdminCoupons
        $scenario->step('ApiAdminCoupons')
            ->post('/api/admin/coupons', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 226 : ApiAdminCouponsCoupon
        $scenario->step('ApiAdminCouponsCoupon')
            ->get('/api/admin/coupons/${coupon}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 227 : ApiAdminCouponsCoupon
        $scenario->step('ApiAdminCouponsCoupon')
            ->put('/api/admin/coupons/${coupon}', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 228 : ApiAdminCouponsCoupon
        $scenario->step('ApiAdminCouponsCoupon')
            ->delete('/api/admin/coupons/${coupon}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 229 : ApiAdminTrips
        $scenario->step('ApiAdminTrips')
            ->get('/api/admin/trips', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 230 : ApiAdminChatConversations
        $scenario->step('ApiAdminChatConversations')
            ->get('/api/admin/chat/conversations', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 231 : ApiAdminChatConversationsConversation
        $scenario->step('ApiAdminChatConversationsConversation')
            ->get('/api/admin/chat/conversations/${conversation}', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 232 : ApiAdminChatConversationsConversationReply
        $scenario->step('ApiAdminChatConversationsConversationReply')
            ->post('/api/admin/chat/conversations/${conversation}/reply', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 233 : ApiAdminChatConversationsConversationClose
        $scenario->step('ApiAdminChatConversationsConversationClose')
            ->post('/api/admin/chat/conversations/${conversation}/close', [
                // Add form fields here
            ], ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 234 : WebHome
        $scenario->step('WebHome')
            ->get('/', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 235 : StoragePath
        $scenario->step('StoragePath')
            ->get('/storage/${path}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 236 : Api.admin
        $scenario->step('Api.admin')
            ->get('/api/v1/admin', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 237 : Admin.index
        $scenario->step('Admin.index')
            ->get('/admin', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 238 : Admin.create
        $scenario->step('Admin.create')
            ->get('/admin/create', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 239 : Admin.store
        $scenario->step('Admin.store')
            ->post('/admin', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 240 : Admin.show
        $scenario->step('Admin.show')
            ->get('/admin/${admin}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 241 : Admin.edit
        $scenario->step('Admin.edit')
            ->get('/admin/${admin}/edit', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 242 : Admin.update
        $scenario->step('Admin.update')
            ->put('/admin/${admin}', [
            '_token' => '${csrf_token}',
            '_method' => 'put',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 243 : Admin.destroy
        $scenario->step('Admin.destroy')
            ->delete('/admin/${admin}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 244 : Api.auth
        $scenario->step('Api.auth')
            ->get('/api/v1/auth', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 245 : Auth.index
        $scenario->step('Auth.index')
            ->get('/auth', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 246 : Auth.create
        $scenario->step('Auth.create')
            ->get('/auth/create', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 247 : Auth.store
        $scenario->step('Auth.store')
            ->post('/auth', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 248 : Auth.show
        $scenario->step('Auth.show')
            ->get('/auth/${auth}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 249 : Auth.edit
        $scenario->step('Auth.edit')
            ->get('/auth/${auth}/edit', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 250 : Auth.update
        $scenario->step('Auth.update')
            ->put('/auth/${auth}', [
            '_token' => '${csrf_token}',
            '_method' => 'put',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 251 : Auth.destroy
        $scenario->step('Auth.destroy')
            ->delete('/auth/${auth}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 252 : Api.driver
        $scenario->step('Api.driver')
            ->get('/api/v1/driver', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 253 : Driver.index
        $scenario->step('Driver.index')
            ->get('/driver', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 254 : Driver.create
        $scenario->step('Driver.create')
            ->get('/driver/create', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 255 : Driver.store
        $scenario->step('Driver.store')
            ->post('/driver', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 256 : Driver.show
        $scenario->step('Driver.show')
            ->get('/driver/${driver}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 257 : Driver.edit
        $scenario->step('Driver.edit')
            ->get('/driver/${driver}/edit', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 258 : Driver.update
        $scenario->step('Driver.update')
            ->put('/driver/${driver}', [
            '_token' => '${csrf_token}',
            '_method' => 'put',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 259 : Driver.destroy
        $scenario->step('Driver.destroy')
            ->delete('/driver/${driver}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 260 : Api.negotiation
        $scenario->step('Api.negotiation')
            ->get('/api/v1/negotiation', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 261 : Negotiation.index
        $scenario->step('Negotiation.index')
            ->get('/negotiation', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 262 : Negotiation.create
        $scenario->step('Negotiation.create')
            ->get('/negotiation/create', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 263 : Negotiation.store
        $scenario->step('Negotiation.store')
            ->post('/negotiation', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 264 : Negotiation.show
        $scenario->step('Negotiation.show')
            ->get('/negotiation/${negotiation}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 265 : Negotiation.edit
        $scenario->step('Negotiation.edit')
            ->get('/negotiation/${negotiation}/edit', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 266 : Negotiation.update
        $scenario->step('Negotiation.update')
            ->put('/negotiation/${negotiation}', [
            '_token' => '${csrf_token}',
            '_method' => 'put',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 267 : Negotiation.destroy
        $scenario->step('Negotiation.destroy')
            ->delete('/negotiation/${negotiation}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 268 : Api.notification
        $scenario->step('Api.notification')
            ->get('/api/v1/notification', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 269 : Notification.index
        $scenario->step('Notification.index')
            ->get('/notification', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 270 : Notification.create
        $scenario->step('Notification.create')
            ->get('/notification/create', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 271 : Notification.store
        $scenario->step('Notification.store')
            ->post('/notification', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 272 : Notification.show
        $scenario->step('Notification.show')
            ->get('/notification/${notification}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 273 : Notification.edit
        $scenario->step('Notification.edit')
            ->get('/notification/${notification}/edit', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 274 : Notification.update
        $scenario->step('Notification.update')
            ->put('/notification/${notification}', [
            '_token' => '${csrf_token}',
            '_method' => 'put',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 275 : Notification.destroy
        $scenario->step('Notification.destroy')
            ->delete('/notification/${notification}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 276 : Api.payment
        $scenario->step('Api.payment')
            ->get('/api/v1/payment', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 277 : Payment.index
        $scenario->step('Payment.index')
            ->get('/payment', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 278 : Payment.create
        $scenario->step('Payment.create')
            ->get('/payment/create', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 279 : Payment.store
        $scenario->step('Payment.store')
            ->post('/payment', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 280 : Payment.show
        $scenario->step('Payment.show')
            ->get('/payment/${payment}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 281 : Payment.edit
        $scenario->step('Payment.edit')
            ->get('/payment/${payment}/edit', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 282 : Payment.update
        $scenario->step('Payment.update')
            ->put('/payment/${payment}', [
            '_token' => '${csrf_token}',
            '_method' => 'put',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 283 : Payment.destroy
        $scenario->step('Payment.destroy')
            ->delete('/payment/${payment}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 284 : Api.pricing
        $scenario->step('Api.pricing')
            ->get('/api/v1/pricing', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 285 : Pricing.index
        $scenario->step('Pricing.index')
            ->get('/pricing', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 286 : Pricing.create
        $scenario->step('Pricing.create')
            ->get('/pricing/create', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 287 : Pricing.store
        $scenario->step('Pricing.store')
            ->post('/pricing', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 288 : Pricing.show
        $scenario->step('Pricing.show')
            ->get('/pricing/${pricing}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 289 : Pricing.edit
        $scenario->step('Pricing.edit')
            ->get('/pricing/${pricing}/edit', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 290 : Pricing.update
        $scenario->step('Pricing.update')
            ->put('/pricing/${pricing}', [
            '_token' => '${csrf_token}',
            '_method' => 'put',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 291 : Pricing.destroy
        $scenario->step('Pricing.destroy')
            ->delete('/pricing/${pricing}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 292 : Api.report
        $scenario->step('Api.report')
            ->get('/api/v1/report', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 293 : Report.index
        $scenario->step('Report.index')
            ->get('/report', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 294 : Report.create
        $scenario->step('Report.create')
            ->get('/report/create', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 295 : Report.store
        $scenario->step('Report.store')
            ->post('/report', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 296 : Report.show
        $scenario->step('Report.show')
            ->get('/report/${report}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 297 : Report.edit
        $scenario->step('Report.edit')
            ->get('/report/${report}/edit', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 298 : Report.update
        $scenario->step('Report.update')
            ->put('/report/${report}', [
            '_token' => '${csrf_token}',
            '_method' => 'put',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 299 : Report.destroy
        $scenario->step('Report.destroy')
            ->delete('/report/${report}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 300 : Api.review
        $scenario->step('Api.review')
            ->get('/api/v1/review', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 301 : Review.index
        $scenario->step('Review.index')
            ->get('/review', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 302 : Review.create
        $scenario->step('Review.create')
            ->get('/review/create', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 303 : Review.store
        $scenario->step('Review.store')
            ->post('/review', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 304 : Review.show
        $scenario->step('Review.show')
            ->get('/review/${review}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 305 : Review.edit
        $scenario->step('Review.edit')
            ->get('/review/${review}/edit', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 306 : Review.update
        $scenario->step('Review.update')
            ->put('/review/${review}', [
            '_token' => '${csrf_token}',
            '_method' => 'put',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 307 : Review.destroy
        $scenario->step('Review.destroy')
            ->delete('/review/${review}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 308 : Api.support
        $scenario->step('Api.support')
            ->get('/api/v1/support', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 309 : Support.index
        $scenario->step('Support.index')
            ->get('/support', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 310 : Support.create
        $scenario->step('Support.create')
            ->get('/support/create', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 311 : Support.store
        $scenario->step('Support.store')
            ->post('/support', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 312 : Support.show
        $scenario->step('Support.show')
            ->get('/support/${support}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 313 : Support.edit
        $scenario->step('Support.edit')
            ->get('/support/${support}/edit', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 314 : Support.update
        $scenario->step('Support.update')
            ->put('/support/${support}', [
            '_token' => '${csrf_token}',
            '_method' => 'put',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 315 : Support.destroy
        $scenario->step('Support.destroy')
            ->delete('/support/${support}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 316 : Api.trip
        $scenario->step('Api.trip')
            ->get('/api/v1/trip', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 317 : Trip.index
        $scenario->step('Trip.index')
            ->get('/trip', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 318 : Trip.create
        $scenario->step('Trip.create')
            ->get('/trip/create', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 319 : Trip.store
        $scenario->step('Trip.store')
            ->post('/trip', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 320 : Trip.show
        $scenario->step('Trip.show')
            ->get('/trip/${trip}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 321 : Trip.edit
        $scenario->step('Trip.edit')
            ->get('/trip/${trip}/edit', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 322 : Trip.update
        $scenario->step('Trip.update')
            ->put('/trip/${trip}', [
            '_token' => '${csrf_token}',
            '_method' => 'put',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 323 : Trip.destroy
        $scenario->step('Trip.destroy')
            ->delete('/trip/${trip}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 324 : Api.user
        $scenario->step('Api.user')
            ->get('/api/v1/user', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 325 : User.index
        $scenario->step('User.index')
            ->get('/user', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 326 : User.create
        $scenario->step('User.create')
            ->get('/user/create', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 327 : User.store
        $scenario->step('User.store')
            ->post('/user', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 328 : User.show
        $scenario->step('User.show')
            ->get('/user/${user}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 329 : User.edit
        $scenario->step('User.edit')
            ->get('/user/${user}/edit', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 330 : User.update
        $scenario->step('User.update')
            ->put('/user/${user}', [
            '_token' => '${csrf_token}',
            '_method' => 'put',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 331 : User.destroy
        $scenario->step('User.destroy')
            ->delete('/user/${user}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 332 : Api.vehicle
        $scenario->step('Api.vehicle')
            ->get('/api/v1/vehicle', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 333 : Vehicle.index
        $scenario->step('Vehicle.index')
            ->get('/vehicle', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 334 : Vehicle.create
        $scenario->step('Vehicle.create')
            ->get('/vehicle/create', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 335 : Vehicle.store
        $scenario->step('Vehicle.store')
            ->post('/vehicle', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 336 : Vehicle.show
        $scenario->step('Vehicle.show')
            ->get('/vehicle/${vehicle}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 337 : Vehicle.edit
        $scenario->step('Vehicle.edit')
            ->get('/vehicle/${vehicle}/edit', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 338 : Vehicle.update
        $scenario->step('Vehicle.update')
            ->put('/vehicle/${vehicle}', [
            '_token' => '${csrf_token}',
            '_method' => 'put',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 339 : Vehicle.destroy
        $scenario->step('Vehicle.destroy')
            ->delete('/vehicle/${vehicle}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 340 : Api.wallet
        $scenario->step('Api.wallet')
            ->get('/api/v1/wallet', ['Authorization' => 'Bearer ${token}', 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->expectStatus(200);

        // Step 341 : Wallet.index
        $scenario->step('Wallet.index')
            ->get('/wallet', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 342 : Wallet.create
        $scenario->step('Wallet.create')
            ->get('/wallet/create', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 343 : Wallet.store
        $scenario->step('Wallet.store')
            ->post('/wallet', [
            '_token' => '${csrf_token}',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 344 : Wallet.show
        $scenario->step('Wallet.show')
            ->get('/wallet/${wallet}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 345 : Wallet.edit
        $scenario->step('Wallet.edit')
            ->get('/wallet/${wallet}/edit', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 346 : Wallet.update
        $scenario->step('Wallet.update')
            ->put('/wallet/${wallet}', [
            '_token' => '${csrf_token}',
            '_method' => 'put',
                // Add form fields here
            ], ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);

        // Step 347 : Wallet.destroy
        $scenario->step('Wallet.destroy')
            ->delete('/wallet/${wallet}', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->expectStatus(200);
    }
}