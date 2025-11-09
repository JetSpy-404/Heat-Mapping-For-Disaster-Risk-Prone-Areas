const express = require('express');
const { protect, authorize } = require('../middleware/authMiddleware');
const { getUserProfile, getUsers } = require('../controllers/userController');

const router = express.Router();

// All routes require authentication
router.use(protect);

// Get user profile with features and permissions
router.get('/profile', getUserProfile);

// Admin only routes
router.get('/', authorize('admin'), getUsers);

module.exports = router;
