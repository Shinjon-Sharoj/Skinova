<?php
/**
 * Application-wide constants (roles, statuses, limits).
 * Safe to include multiple times.
 */
if (defined('SKINOVA_CONSTANTS_LOADED')) {
    return;
}
define('SKINOVA_CONSTANTS_LOADED', true);

// Roles
const ROLE_CUSTOMER   = 'customer';
const ROLE_ADMIN      = 'admin';
const ROLE_CONSULTANT = 'consultant';

// Skin types / enums shared by forms and queries
const SKIN_TYPES = ['normal', 'dry', 'oily', 'combination', 'sensitive'];

const ORDER_STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
const PAYMENT_STATUSES = ['pending', 'paid', 'failed', 'refunded', 'partially_refunded'];
const CONSULTATION_STATUSES = ['requested', 'confirmed', 'in_progress', 'completed', 'cancelled', 'rejected'];
const RETURN_STATUSES = ['requested', 'approved', 'rejected', 'received', 'refunded'];
const ROUTINE_STEPS = ['cleanser', 'toner', 'serum', 'moisturizer', 'sunscreen', 'treatment', 'mask', 'exfoliant', 'eye_care', 'other'];

// Allowed order status transitions (forward-only flow; cancel allowed before shipping)
const ORDER_TRANSITIONS = [
    'pending'    => ['confirmed', 'cancelled'],
    'confirmed'  => ['processing', 'cancelled'],
    'processing' => ['shipped', 'cancelled'],
    'shipped'    => ['delivered'],
    'delivered'  => [],
    'cancelled'  => [],
];

// Upload limits
const MAX_IMAGE_BYTES    = 5 * 1024 * 1024;   // 5 MB
const MAX_DOCUMENT_BYTES = 8 * 1024 * 1024;   // 8 MB
const ALLOWED_IMAGE_MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const ALLOWED_DOCUMENT_MIMES = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

// Pagination
const PRODUCTS_PER_PAGE = 12;
const ADMIN_PER_PAGE    = 20;

// Messages shown with every AI / skin related result
const AI_DISCLAIMER = 'This information is for general education only and is not medical advice or a diagnosis. '
    . 'If you have persistent, painful, or worsening skin problems, please consult a qualified dermatologist or book a consultation with one of our skincare professionals.';