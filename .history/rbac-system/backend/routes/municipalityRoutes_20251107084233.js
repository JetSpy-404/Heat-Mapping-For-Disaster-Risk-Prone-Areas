const express = require('express');
const { body } = require('express-validator');
const { protect, featureAccess, checkPermission, authorize } = require('../middleware/authMiddleware');
const {
  getMunicipalities,
  createMunicipality
} = require('../controllers/municipalityController');

const router = express.Router();

// All municipality routes require authentication and admin role
router.use(protect);
router.use(authorize('admin')); // Only admin can access municipality
router.use(featureAccess('municipality'));

// GET /api/municipality - Read municipality (admin only)
router.get('/', checkPermission('municipality', 'read'), getMunicipalities);

// POST /api/municipality - Create municipality (admin only)
router.post('/', [
  checkPermission('municipality', 'create'),
  body('name').notEmpty().withMessage('Municipality name is required')
], createMunicipality);

module.exports = router;
