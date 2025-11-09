const { body, validationResult } = require('express-validator');

// Mock data for barangay operations (replace with actual model when needed)
let barangays = [
  { id: 1, name: 'Barangay 1' },
  { id: 2, name: 'Barangay 2' }
];

// @desc    Get all barangays
// @route   GET /api/barangay
// @access  Private (with feature access)
exports.getBarangays = async (req, res) => {
  try {
    res.status(200).json({
      success: true,
      data: barangays
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Create barangay
// @route   POST /api/barangay
// @access  Private (with permission)
exports.createBarangay = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({
        success: false,
        message: 'Validation failed',
        errors: errors.array()
      });
    }

    const { name } = req.body;
    const newBarangay = {
      id: barangays.length + 1,
      name
    };

    barangays.push(newBarangay);

    res.status(201).json({
      success: true,
      data: newBarangay
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Update barangay
// @route   PUT /api/barangay/:id
// @access  Private (with permission)
exports.updateBarangay = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({
        success: false,
        message: 'Validation failed',
        errors: errors.array()
      });
    }

    const { id } = req.params;
    const { name } = req.body;

    const barangayIndex = barangays.findIndex(b => b.id === parseInt(id));
    if (barangayIndex === -1) {
      return res.status(404).json({
        success: false,
        message: 'Barangay not found'
      });
    }

    barangays[barangayIndex].name = name;

    res.status(200).json({
      success: true,
      data: barangays[barangayIndex]
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Delete barangay
// @route   DELETE /api/barangay/:id
// @access  Private (with permission)
exports.deleteBarangay = async (req, res) => {
  try {
    const { id } = req.params;

    const barangayIndex = barangays.findIndex(b => b.id === parseInt(id));
    if (barangayIndex === -1) {
      return res.status(404).json({
        success: false,
        message: 'Barangay not found'
      });
    }

    barangays.splice(barangayIndex, 1);

    res.status(200).json({
      success: true,
      message: 'Barangay deleted successfully'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      message: error.message
    });
  }
};
