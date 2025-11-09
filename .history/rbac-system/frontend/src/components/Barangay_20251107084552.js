import React, { useState, useEffect } from 'react';
import { useAuth } from '../contexts/AuthContext';
import axios from 'axios';

const Barangay = () => {
  const { hasPermission } = useAuth();
  const [barangays, setBarangays] = useState([]);
  const [formData, setFormData] = useState({ name: '' });
  const [editingId, setEditingId] = useState(null);

  // Load barangays
  useEffect(() => {
    loadBarangays();
  }, []);

  const loadBarangays = async () => {
    try {
      const res = await axios.get('/api/barangay');
      setBarangays(res.data.data);
    } catch (error) {
      console.error('Failed to load barangays:', error);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      if (editingId) {
        // Update barangay
        await axios.put(`/api/barangay/${editingId}`, formData);
      } else {
        // Create barangay
        await axios.post('/api/barangay', formData);
      }
      setFormData({ name: '' });
      setEditingId(null);
      loadBarangays();
    } catch (error) {
      console.error('Failed to save barangay:', error);
    }
  };

  const handleEdit = (barangay) => {
    setFormData({ name: barangay.name });
    setEditingId(barangay.id);
  };

  const handleDelete = async (id) => {
    if (window.confirm('Are you sure you want to delete this barangay?')) {
      try {
        await axios.delete(`/api/barangay/${id}`);
        loadBarangays();
      } catch (error) {
        console.error('Failed to delete barangay:', error);
      }
    }
  };

  return (
    <div className="barangay-container">
      <h1>Barangay Management</h1>

      {/* Create/Edit Form - Show if user has create or update permission */}
      {(hasPermission('barangay', 'create') || hasPermission('barangay', 'update')) && (
        <form onSubmit={handleSubmit} className="barangay-form">
          <input
            type="text"
            placeholder="Barangay Name"
            value={formData.name}
            onChange={(e) => setFormData({ name: e.target.value })}
            required
          />
          <button type="submit">
            {editingId ? 'Update' : 'Create'} Barangay
          </button>
          {editingId && (
            <button type="button" onClick={() => {
              setFormData({ name: '' });
              setEditingId(null);
            }}>
              Cancel
            </button>
          )}
        </form>
      )}

      {/* Barangay List */}
      <div className="barangay-list">
        <h2>Barangays</h2>
        {barangays.map(barangay => (
          <div key={barangay.id} className="barangay-item">
            <span>{barangay.name}</span>

            {/* Action buttons - only show if user has permissions */}
            <div className="barangay-actions">
              {hasPermission('barangay', 'update') && (
                <button onClick={() => handleEdit(barangay)}>Edit</button>
              )}
              {hasPermission('barangay', 'delete') && (
                <button onClick={() => handleDelete(barangay.id)}>Delete</button>
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default Barangay;
