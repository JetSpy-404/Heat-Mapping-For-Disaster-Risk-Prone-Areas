const express = require('express');
const { body } = require('express-validator');
const { protect, featureAccess, checkPermission } = require('../middleware/authMiddleware');
const {
  getBarangays,
  createBarangay,
  updateBarangay,
  deleteBarangay
} = require('../controllers/barangayController');

const router = express.Router();

// All barangay routes require authentication
router.use(protect);

// All barangay routes require feature access
router.use(featureAccess('barangay'));

// GET /api/barangay - Read barangay (both admin and user can read)
router.get('/', checkPermission('barangay', 'read'), getBarangays);

// POST /api/barangay - Create barangay (both admin and user can create)
router.post('/', [
  checkPermission('barangay', 'create'),
  body('name').notEmpty().withMessage('Barangay name is required')
], createBarangay);

// PUT /api/barangay/:id - Update barangay (both admin and user can update)
router.put('/:id', [
  checkPermission('barangay', 'update'),
  body('name').notEmpty().withMessage('Barangay name is required')
], updateBarangay);

// DELETE /api/barangay/:id - Delete barangay (both admin and user can delete)
router.delete('/:id', checkPermission('barangay', 'delete'), deleteBarangay);

module.exports = router;
