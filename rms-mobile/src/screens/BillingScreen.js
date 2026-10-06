import React, { useState, useEffect } from 'react';
import { 
  View, Text, StyleSheet, ScrollView, TouchableOpacity, 
  SafeAreaView, TextInput, ActivityIndicator, Alert, Modal, FlatList, Platform, StatusBar
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import axios from 'axios';

const BillingScreen = ({ navigation }) => {
  const [renters, setRenters] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [modalVisible, setModalVisible] = useState(false);
  const [previewVisible, setPreviewVisible] = useState(false);

  // Form State
  const [selectedRenter, setSelectedRenter] = useState(null);
  const [currentReading, setCurrentReading] = useState('');
  const [ratePerUnit, setRatePerUnit] = useState('8.0');
  const [rent, setRent] = useState('');
  const [maintenance, setMaintenance] = useState('');
  const [dues, setDues] = useState('');
  const [extraCharges, setExtraCharges] = useState('');
  const [extraDesc, setExtraDesc] = useState('');
  
  const currentMonth = new Date().toLocaleString('default', { month: 'long', year: 'numeric' });

  useEffect(() => {
    fetchRenters();
  }, []);

  const fetchRenters = async () => {
    try {
      const response = await axios.get('http://192.168.1.19/renter-system/api/admin/get_renters_for_billing.php');
      if (response.data.status === 'success') {
        setRenters(response.data.data);
      }
    } catch (error) {
      Alert.alert('Error', 'Failed to fetch residents data.');
    } finally {
      setIsLoading(false);
    }
  };

  const selectRenter = (renter) => {
    setSelectedRenter(renter);
    setRent(renter.fixed_rent.toString());
    setMaintenance(renter.fixed_maintenance.toString());
    setDues(renter.pending_adjustment.toString());
    setCurrentReading('');
    setExtraCharges('');
    setExtraDesc('');
    setModalVisible(false);
  };

  // Calculations
  const prevReadingVal = selectedRenter ? selectedRenter.last_reading : 0;
  const currentReadingVal = parseInt(currentReading) || 0;
  const rateVal = parseFloat(ratePerUnit) || 0;
  const rentVal = parseFloat(rent) || 0;
  const maintVal = parseFloat(maintenance) || 0;
  const duesVal = parseFloat(dues) || 0;
  const extraVal = parseFloat(extraCharges) || 0;

  const handleInputFocus = (value, setter) => {
    if (value === '0' || value === '0.00' || value === 0) {
      setter('');
    }
  };

  const unitsConsumed = Math.max(0, currentReadingVal - prevReadingVal);
  const electricityCost = unitsConsumed * rateVal;
  const totalPayable = electricityCost + rentVal + maintVal + duesVal + extraVal;

  const handleCheckout = () => {
    if (!selectedRenter) {
      Alert.alert('Validation Error', 'Please select a resident.');
      return;
    }
    if (currentReadingVal <= 0 || currentReadingVal < prevReadingVal) {
      Alert.alert('Validation Error', 'Current reading must be valid and greater than previous reading.');
      return;
    }
    setPreviewVisible(true);
  };

  const confirmAndGenerate = async () => {
    setPreviewVisible(false);
    setIsSubmitting(true);
    try {
      const payload = {
        user_id: selectedRenter.id,
        bill_month: currentMonth,
        previous_reading: prevReadingVal,
        current_reading: currentReadingVal,
        rate_per_unit: rateVal,
        rent_amount: rentVal,
        maintenance: maintVal,
        dues: duesVal,
        extra_charges: extraVal,
        extra_charges_desc: extraDesc
      };

      const response = await axios.post('http://192.168.1.19/renter-system/api/admin/mobile_save_bill.php', payload);
      
      if (response.data.status === 'success') {
        Alert.alert('Success', 'Bill generated successfully!', [
          { text: 'OK', onPress: () => navigation.goBack() }
        ]);
      } else {
        Alert.alert('Error', response.data.message || 'Failed to generate bill.');
      }
    } catch (error) {
      Alert.alert('Error', 'Network error or server error.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const renderRenterItem = ({ item }) => (
    <TouchableOpacity style={styles.renterItem} onPress={() => selectRenter(item)}>
      <View style={styles.renterAvatar}>
        <Text style={styles.renterAvatarText}>{item.name.charAt(0)}</Text>
      </View>
      <View style={styles.renterInfo}>
        <Text style={styles.renterName}>{item.name}</Text>
        <Text style={styles.renterRoom}>Room {item.room_no}</Text>
      </View>
      <View style={styles.renterSelectIcon}>
        <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
      </View>
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
          <Ionicons name="arrow-back" size={24} color="#0F172A" />
        </TouchableOpacity>
        <View style={styles.headerCenter}>
          <Text style={styles.headerTitle}>New Bill</Text>
          <Text style={styles.headerSubtitle}>{currentMonth}</Text>
        </View>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView 
        style={styles.scrollView} 
        contentContainerStyle={styles.scrollContent} 
        showsVerticalScrollIndicator={false}
        keyboardDismissMode="on-drag"
        keyboardShouldPersistTaps="handled"
      >
        
        {/* Resident Selection Card */}
        <Text style={styles.sectionTitle}>Resident Details</Text>
        <TouchableOpacity style={styles.premiumCard} onPress={() => setModalVisible(true)} activeOpacity={0.8}>
          <View style={styles.cardHeader}>
            <View style={[styles.iconBox, { backgroundColor: '#EEF2FF' }]}>
              <Ionicons name="person" size={20} color="#4F46E5" />
            </View>
            <Text style={styles.cardTitle}>Choose Account</Text>
          </View>
          
          <View style={styles.residentSelector}>
            {selectedRenter ? (
              <View style={styles.selectedResidentRow}>
                <View style={styles.selectedAvatar}>
                  <Text style={styles.selectedAvatarText}>{selectedRenter.name.charAt(0)}</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.selectedName}>{selectedRenter.name}</Text>
                  <Text style={styles.selectedRoom}>Room {selectedRenter.room_no}</Text>
                </View>
                <View style={styles.changeBadge}>
                  <Text style={styles.changeBadgeText}>Change</Text>
                </View>
              </View>
            ) : (
              <View style={styles.emptySelector}>
                <Text style={styles.emptySelectorText}>Tap to select a resident...</Text>
                <Ionicons name="chevron-down" size={20} color="#94A3B8" />
              </View>
            )}
          </View>
        </TouchableOpacity>

        {/* Electricity Card */}
        <Text style={styles.sectionTitle}>Electricity Usage</Text>
        <View style={styles.premiumCard}>
          <View style={styles.cardHeader}>
            <View style={[styles.iconBox, { backgroundColor: '#FEF9C3' }]}>
              <Ionicons name="flash" size={20} color="#EAB308" />
            </View>
            <Text style={styles.cardTitle}>Meter Readings</Text>
            {unitsConsumed > 0 && (
              <View style={styles.unitsBadge}>
                <Text style={styles.unitsBadgeText}>{unitsConsumed} units</Text>
              </View>
            )}
          </View>

          <View style={styles.formRow}>
            <View style={styles.inputGroup}>
              <Text style={styles.inputLabel}>Previous</Text>
              <View style={styles.inputBoxDisabled}>
                <Text style={styles.disabledText}>{prevReadingVal}</Text>
              </View>
            </View>
            <View style={styles.inputGroup}>
              <Text style={styles.inputLabel}>Current *</Text>
              <TextInput 
                style={styles.inputBox}
                keyboardType="numeric"
                placeholder="0"
                value={currentReading}
                onChangeText={setCurrentReading}
                placeholderTextColor="#94A3B8"
              />
            </View>
          </View>
          
          <View style={styles.divider} />
          
          <View style={styles.inputGroupFull}>
            <Text style={styles.inputLabel}>Rate / Unit (₹)</Text>
            <TextInput 
              style={styles.inputBox}
              keyboardType="numeric"
              value={ratePerUnit}
              onChangeText={setRatePerUnit}
              onFocus={() => handleInputFocus(ratePerUnit, setRatePerUnit)}
            />
          </View>
        </View>

        {/* Fixed Charges Card */}
        <Text style={styles.sectionTitle}>Fixed Charges & Arrears</Text>
        <View style={styles.premiumCard}>
          <View style={styles.cardHeader}>
            <View style={[styles.iconBox, { backgroundColor: '#ECFDF5' }]}>
              <Ionicons name="home" size={20} color="#10B981" />
            </View>
            <Text style={styles.cardTitle}>Monthly Due</Text>
          </View>

          <View style={styles.formRow}>
            <View style={styles.inputGroup}>
              <Text style={styles.inputLabel}>Rent (₹)</Text>
              <TextInput 
                style={styles.inputBox}
                keyboardType="numeric"
                value={rent}
                onChangeText={setRent}
                onFocus={() => handleInputFocus(rent, setRent)}
              />
            </View>
            <View style={styles.inputGroup}>
              <Text style={styles.inputLabel}>Maintenance (₹)</Text>
              <TextInput 
                style={styles.inputBox}
                keyboardType="numeric"
                value={maintenance}
                onChangeText={setMaintenance}
                onFocus={() => handleInputFocus(maintenance, setMaintenance)}
              />
            </View>
          </View>
          
          <View style={styles.divider} />

          <View style={styles.inputGroupFull}>
            <Text style={styles.inputLabel}>Past Dues (₹)</Text>
            <TextInput 
              style={[styles.inputBox, duesVal > 0 && { color: '#EF4444' }]}
              keyboardType="numeric"
              value={dues}
              onChangeText={setDues}
              onFocus={() => handleInputFocus(dues, setDues)}
            />
          </View>
        </View>

        {/* Adjustments Card */}
        <Text style={styles.sectionTitle}>Extra Adjustments</Text>
        <View style={styles.premiumCard}>
          <View style={styles.cardHeader}>
            <View style={[styles.iconBox, { backgroundColor: '#F3E8FF' }]}>
              <Ionicons name="construct" size={20} color="#A855F7" />
            </View>
            <Text style={styles.cardTitle}>Additional Fees</Text>
          </View>

          <View style={styles.inputGroupFull}>
            <Text style={styles.inputLabel}>Extra Amount (₹)</Text>
            <TextInput 
              style={styles.inputBox}
              keyboardType="numeric"
              placeholder="0"
              value={extraCharges}
              onChangeText={setExtraCharges}
              onFocus={() => handleInputFocus(extraCharges, setExtraCharges)}
              placeholderTextColor="#94A3B8"
            />
          </View>
          <View style={[styles.inputGroupFull, { marginTop: 16 }]}>
            <Text style={styles.inputLabel}>Reason (Optional)</Text>
            <TextInput 
              style={styles.inputBox}
              placeholder="e.g., Water bill, Plumber"
              value={extraDesc}
              onChangeText={setExtraDesc}
              placeholderTextColor="#94A3B8"
            />
          </View>
        </View>

      </ScrollView>

      {/* Sticky Bottom Checkout Footer */}
      <View style={styles.bottomFooter}>
        <View style={styles.footerDetails}>
          <Text style={styles.footerLabel}>Total Payable</Text>
          <Text style={styles.footerAmount}>₹{totalPayable.toLocaleString('en-IN')}</Text>
        </View>
        <TouchableOpacity 
          style={styles.checkoutBtnWrap} 
          onPress={handleCheckout}
          disabled={!selectedRenter || isSubmitting}
          activeOpacity={0.8}
        >
          <LinearGradient
            colors={(!selectedRenter || isSubmitting) ? ['#94A3B8', '#64748B'] : ['#6366F1', '#4F46E5']}
            start={{ x: 0, y: 0 }} end={{ x: 1, y: 0 }}
            style={styles.checkoutBtn}
          >
            {isSubmitting ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <>
                <Text style={styles.checkoutBtnText}>Checkout</Text>
                <Ionicons name="arrow-forward" size={20} color="#fff" style={{ marginLeft: 6 }} />
              </>
            )}
          </LinearGradient>
        </TouchableOpacity>
      </View>

      {/* Renter Selection Modal */}
      <Modal visible={modalVisible} animationType="slide" transparent={true}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Select Resident</Text>
              <TouchableOpacity onPress={() => setModalVisible(false)} style={styles.closeBtn}>
                <Ionicons name="close" size={24} color="#0F172A" />
              </TouchableOpacity>
            </View>
            {isLoading ? (
              <ActivityIndicator size="large" color="#4F46E5" style={{ marginTop: 50 }} />
            ) : (
              <FlatList 
                data={renters}
                keyExtractor={(item) => item.id.toString()}
                renderItem={renderRenterItem}
                contentContainerStyle={{ paddingBottom: 40 }}
                showsVerticalScrollIndicator={false}
              />
            )}
          </View>
        </View>
      </Modal>

      {/* Bill Preview Modal */}
      <Modal visible={previewVisible} animationType="slide" transparent={true}>
        <View style={styles.modalOverlay}>
          <View style={[styles.modalContent, { height: 'auto', maxHeight: '85%' }]}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Bill Preview</Text>
              <TouchableOpacity onPress={() => setPreviewVisible(false)} style={styles.closeBtn}>
                <Ionicons name="close" size={24} color="#0F172A" />
              </TouchableOpacity>
            </View>
            <ScrollView contentContainerStyle={{ paddingBottom: 40 }} showsVerticalScrollIndicator={false}>
              
              <LinearGradient colors={['#6366F1', '#4F46E5']} style={[styles.premiumCard, { padding: 24, marginTop: 10 }]}>
                <View style={{ flexDirection: 'row', justifyContent: 'space-between', marginBottom: 20 }}>
                  <View>
                    <Text style={{ color: '#C7D2FE', fontSize: 12, fontWeight: '600', textTransform: 'uppercase', marginBottom: 4 }}>Resident</Text>
                    <Text style={{ color: '#fff', fontSize: 16, fontWeight: '700' }}>{selectedRenter?.name}</Text>
                  </View>
                  <View style={{ alignItems: 'flex-end' }}>
                    <Text style={{ color: '#C7D2FE', fontSize: 12, fontWeight: '600', textTransform: 'uppercase', marginBottom: 4 }}>Billing Cycle</Text>
                    <Text style={{ color: '#fff', fontSize: 14, fontWeight: '600' }}>{currentMonth}</Text>
                  </View>
                </View>

                <View style={{ marginBottom: 24 }}>
                  <Text style={{ color: '#C7D2FE', fontSize: 12, fontWeight: '600', textTransform: 'uppercase', marginBottom: 4 }}>Total Payable</Text>
                  <Text style={{ color: '#fff', fontSize: 40, fontWeight: '800', letterSpacing: -1 }}>₹{totalPayable.toLocaleString('en-IN')}</Text>
                </View>

                <View style={{ backgroundColor: 'rgba(255,255,255,0.15)', borderRadius: 16, padding: 16, borderWidth: 1, borderColor: 'rgba(255,255,255,0.2)' }}>
                  <View style={{ flexDirection: 'row', justifyContent: 'space-between', marginBottom: 8 }}>
                    <Text style={{ color: '#E0E7FF', fontSize: 13, fontWeight: '500' }}>Electricity ({unitsConsumed} units)</Text>
                    <Text style={{ color: '#fff', fontSize: 13, fontWeight: '700' }}>₹{electricityCost.toLocaleString('en-IN')}</Text>
                  </View>
                  <View style={{ flexDirection: 'row', justifyContent: 'space-between', marginBottom: 8 }}>
                    <Text style={{ color: '#E0E7FF', fontSize: 13, fontWeight: '500' }}>Rent + Maintenance</Text>
                    <Text style={{ color: '#fff', fontSize: 13, fontWeight: '700' }}>₹{(rentVal + maintVal).toLocaleString('en-IN')}</Text>
                  </View>
                  <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                    <Text style={{ color: '#E0E7FF', fontSize: 13, fontWeight: '500' }}>Arrears + Extra</Text>
                    <Text style={{ color: '#fff', fontSize: 13, fontWeight: '700' }}>₹{(duesVal + extraVal).toLocaleString('en-IN')}</Text>
                  </View>
                </View>
              </LinearGradient>
              
              <TouchableOpacity style={styles.confirmBtn} onPress={confirmAndGenerate}>
                <Ionicons name="checkmark-circle" size={20} color="#fff" style={{ marginRight: 8 }} />
                <Text style={styles.confirmBtnText}>Confirm & Generate Bill</Text>
              </TouchableOpacity>
              
            </ScrollView>
          </View>
        </View>
      </Modal>

    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC', paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight + 20 : 30 },
  header: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingHorizontal: 20, paddingTop: 10, paddingBottom: 15,
    backgroundColor: '#F8FAFC',
    borderBottomWidth: 1, borderBottomColor: 'rgba(0,0,0,0.02)'
  },
  headerCenter: { alignItems: 'center' },
  headerTitle: { fontSize: 18, fontWeight: '800', color: '#0F172A', letterSpacing: 0.5 },
  headerSubtitle: { fontSize: 12, color: '#64748B', fontWeight: '500', marginTop: 2 },
  backButton: {
    width: 40, height: 40, borderRadius: 20, backgroundColor: '#fff',
    justifyContent: 'center', alignItems: 'center',
    shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.05, shadowRadius: 4, elevation: 2,
  },
  
  scrollView: { flex: 1 },
  scrollContent: { paddingHorizontal: 20, paddingTop: 5, paddingBottom: 110 }, // paddingBottom avoids footer overlap
  
  sectionTitle: { fontSize: 14, fontWeight: '700', color: '#64748B', textTransform: 'uppercase', letterSpacing: 1, marginBottom: 12, marginTop: 10, marginLeft: 4 },
  
  premiumCard: {
    backgroundColor: '#fff', borderRadius: 24, padding: 20, marginBottom: 28,
    shadowColor: '#4F46E5', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.08, shadowRadius: 12, elevation: 4,
    borderWidth: 1, borderColor: '#F1F5F9'
  },
  cardHeader: { flexDirection: 'row', alignItems: 'center', marginBottom: 20 },
  iconBox: { width: 36, height: 36, borderRadius: 12, justifyContent: 'center', alignItems: 'center', marginRight: 12 },
  cardTitle: { fontSize: 16, fontWeight: '700', color: '#0F172A', flex: 1 },
  unitsBadge: { backgroundColor: '#EEF2FF', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 12 },
  unitsBadgeText: { color: '#4F46E5', fontSize: 12, fontWeight: '700' },
  
  divider: { height: 1, backgroundColor: '#F1F5F9', marginVertical: 20 },

  // Resident Selector
  residentSelector: { backgroundColor: '#F8FAFC', borderRadius: 16, padding: 12, borderWidth: 1, borderColor: '#E2E8F0' },
  emptySelector: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 8, paddingHorizontal: 4 },
  emptySelectorText: { color: '#94A3B8', fontSize: 15, fontWeight: '500' },
  selectedResidentRow: { flexDirection: 'row', alignItems: 'center' },
  selectedAvatar: { width: 40, height: 40, borderRadius: 20, backgroundColor: '#4F46E5', justifyContent: 'center', alignItems: 'center', marginRight: 12 },
  selectedAvatarText: { color: '#fff', fontSize: 16, fontWeight: '700' },
  selectedName: { fontSize: 16, fontWeight: '700', color: '#0F172A' },
  selectedRoom: { fontSize: 13, color: '#64748B', marginTop: 2 },
  changeBadge: { backgroundColor: '#F1F5F9', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 12 },
  changeBadgeText: { color: '#64748B', fontSize: 12, fontWeight: '600' },

  // Forms
  formRow: { flexDirection: 'row', gap: 16 },
  inputGroup: { flex: 1 },
  inputGroupFull: { width: '100%' },
  inputLabel: { fontSize: 12, color: '#64748B', fontWeight: '600', marginBottom: 8, marginLeft: 4 },
  inputBox: {
    backgroundColor: '#F8FAFC', borderRadius: 16, borderWidth: 1, borderColor: '#E2E8F0',
    paddingHorizontal: 16, height: 54, fontSize: 16, color: '#0F172A', fontWeight: '600'
  },
  inputBoxDisabled: {
    backgroundColor: '#F1F5F9', borderRadius: 16, borderWidth: 1, borderColor: 'transparent',
    paddingHorizontal: 16, height: 54, justifyContent: 'center'
  },
  disabledText: { fontSize: 16, color: '#94A3B8', fontWeight: '600' },

  // Sticky Footer
  bottomFooter: {
    position: 'absolute', bottom: 0, left: 0, right: 0,
    backgroundColor: '#fff',
    borderTopLeftRadius: 32, borderTopRightRadius: 32,
    paddingHorizontal: 24, paddingTop: 24, paddingBottom: Platform.OS === 'ios' ? 34 : 24,
    shadowColor: '#000', shadowOffset: { width: 0, height: -10 }, shadowOpacity: 0.08, shadowRadius: 20, elevation: 20,
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
  },
  footerDetails: { flex: 1 },
  footerLabel: { fontSize: 13, color: '#64748B', fontWeight: '600', textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 4 },
  footerAmount: { fontSize: 32, color: '#0F172A', fontWeight: '800', letterSpacing: -1 },
  checkoutBtnWrap: { flex: 1, marginLeft: 16 },
  checkoutBtn: {
    height: 56, borderRadius: 100, flexDirection: 'row', justifyContent: 'center', alignItems: 'center',
    shadowColor: '#4F46E5', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.3, shadowRadius: 8, elevation: 5
  },
  checkoutBtnText: { color: '#fff', fontSize: 16, fontWeight: '700', letterSpacing: 0.5 },

  // Modal
  modalOverlay: { flex: 1, backgroundColor: 'rgba(15,23,42,0.6)', justifyContent: 'flex-end' },
  modalContent: { backgroundColor: '#F8FAFC', borderTopLeftRadius: 32, borderTopRightRadius: 32, height: '85%', paddingHorizontal: 24 },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 24, borderBottomWidth: 1, borderBottomColor: '#F1F5F9', marginBottom: 16 },
  modalTitle: { fontSize: 20, fontWeight: '800', color: '#0F172A' },
  closeBtn: { width: 40, height: 40, borderRadius: 20, backgroundColor: '#F1F5F9', justifyContent: 'center', alignItems: 'center' },
  renterItem: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', padding: 16, borderRadius: 20, marginBottom: 12, shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.03, shadowRadius: 4, elevation: 1 },
  renterAvatar: { width: 48, height: 48, borderRadius: 24, backgroundColor: '#EEF2FF', justifyContent: 'center', alignItems: 'center', marginRight: 16 },
  renterAvatarText: { color: '#4F46E5', fontSize: 18, fontWeight: '700' },
  renterInfo: { flex: 1 },
  renterName: { fontSize: 16, fontWeight: '700', color: '#0F172A', marginBottom: 4 },
  renterRoom: { fontSize: 13, color: '#64748B', fontWeight: '500' },
  renterSelectIcon: { width: 32, height: 32, borderRadius: 16, backgroundColor: '#F8FAFC', justifyContent: 'center', alignItems: 'center' },
  confirmBtn: { backgroundColor: '#10B981', flexDirection: 'row', height: 56, borderRadius: 16, justifyContent: 'center', alignItems: 'center', marginTop: 10, shadowColor: '#10B981', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.3, shadowRadius: 8, elevation: 5 },
  confirmBtnText: { color: '#fff', fontSize: 16, fontWeight: '700' },
});

export default BillingScreen;
