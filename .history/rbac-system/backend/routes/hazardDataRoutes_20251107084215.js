const express = require('express');
const { body } = require('express-validator');
const { protect, featureAccess, checkPermission } = require('../middleware/authMiddleware');
const {
  getHazardData,
  createHazardData
} = require('../controllers/hazardDataController');

const router = express.Router();

// All hazard data routes require authentication
router.use(protect);
router.use(featureAccess('hazard_data'));

// GET /api/hazard-data - Read hazard data (both admin and user can read)
router.get('/', checkPermission('hazard_data', 'read'), getHazardData);

// POST /api/hazard-data - Create hazard data (admin only)
router.post('/', [
  checkPermission('hazard_data', 'create'),
  body('type').notEmpty().withMessage('Hazard type is required')
], createHazardData);

module.exports = router;
