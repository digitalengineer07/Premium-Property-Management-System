import React, { useState, useCallback } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, RefreshControl, SafeAreaView, Dimensions, Image, ActivityIndicator, Alert, StatusBar, Modal, TextInput, KeyboardAvoidingView, Platform } from 'react-native';
import { Ionicons, MaterialCommunityIcons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { useFocusEffect } from '@react-navigation/native';
import * as DocumentPicker from 'expo-document-picker';
import axios from 'axios';

const { width } = Dimensions.get('window');
const API_URL = 'http://192.168.1.19/renter-system/api/resident/profile.php';

export default function ResidentProfileScreen({ route, navigation }) {
  const { token } = route.params || {};

  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [profile, setProfile] = useState({});

  const fetchProfileData = async () => {
    try {
      const response = await axios.get(`${API_URL}?token=${encodeURIComponent(token)}`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Cache-Control': 'no-cache' }
      });
      if (response.data.status === 'success') {
        setProfile(response.data.data);
        setEditForm({
          name: response.data.data.name || response.data.data.username || '',
          phone: response.data.data.phone || '',
          whatsapp: response.data.data.whatsapp || '',
          email: response.data.data.email || ''
        });
      } else {
        Alert.alert('Error', response.data.message);
      }
    } catch (error) {
      if (error.response && error.response.status === 401) {
        Alert.alert('Session Expired', 'Please login again.');
        navigation.replace('Login');
      } else {
        Alert.alert('Network Error', 'Failed to load profile.');
      }
    } finally {
      setIsLoading(false);
      setRefreshing(false);
    }
  };

  useFocusEffect(
    useCallback(() => {
      if (token) fetchProfileData();
      else navigation.replace('Login');
    }, [token])
  );

  const onRefresh = () => {
    setRefreshing(true);
    fetchProfileData();
  };

  const handleLogout = () => {
    Alert.alert(
      "Logout",
      "Are you sure you want to log out?",
      [
        { text: "Cancel", style: "cancel" },
        { text: "Logout", style: "destructive", onPress: () => navigation.replace('Login') }
      ]
    );
  };

  const [isEditModalVisible, setIsEditModalVisible] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [editForm, setEditForm] = useState({ name: '', phone: '', whatsapp: '', email: '' });

  const [isPasswordModalVisible, setIsPasswordModalVisible] = useState(false);
  const [isChangingPassword, setIsChangingPassword] = useState(false);
  const [passwordForm, setPasswordForm] = useState({ currentPassword: '', newPassword: '', confirmPassword: '' });
  const [showPasswords, setShowPasswords] = useState(false);

  const handleSaveProfile = async () => {
    if (!editForm.name.trim()) {
      Alert.alert('Validation Error', 'Name is required.');
      return;
    }
    setIsSaving(true);
    try {
      const response = await axios.post(
        'http://192.168.1.19/renter-system/api/resident/update_profile.php',
        editForm,
        { headers: { 'Authorization': `Bearer ${token}` } }
      );
      if (response.data.status === 'success') {
        Alert.alert('Success', 'Profile updated successfully!');
        setIsEditModalVisible(false);
        fetchProfileData(); // refresh data
      } else {
        Alert.alert('Error', response.data.message);
      }
    } catch (error) {
      Alert.alert('Error', 'Failed to update profile. Please check your connection.');
    } finally {
      setIsSaving(false);
    }
  };

  const handleChangePassword = async () => {
    if (!passwordForm.currentPassword || !passwordForm.newPassword || !passwordForm.confirmPassword) {
      Alert.alert('Validation Error', 'All fields are required.');
      return;
    }
    if (passwordForm.newPassword.length < 6) {
      Alert.alert('Validation Error', 'New password must be at least 6 characters.');
      return;
    }
    if (passwordForm.newPassword !== passwordForm.confirmPassword) {
      Alert.alert('Validation Error', 'New passwords do not match.');
      return;
    }

    setIsChangingPassword(true);
    try {
      const response = await axios.post(
        'http://192.168.1.19/renter-system/api/resident/change_password.php',
        { current_password: passwordForm.currentPassword, new_password: passwordForm.newPassword },
        { headers: { 'Authorization': `Bearer ${token}` } }
      );
      if (response.data.status === 'success') {
        Alert.alert('Success', 'Password changed successfully!');
        setIsPasswordModalVisible(false);
        setPasswordForm({ currentPassword: '', newPassword: '', confirmPassword: '' });
      } else {
        Alert.alert('Error', response.data.message);
      }
    } catch (error) {
      Alert.alert('Error', error.response?.data?.message || 'Failed to change password. Please check your connection.');
    } finally {
      setIsChangingPassword(false);
    }
  };

  const [isUploadingAadhar, setIsUploadingAadhar] = useState(false);

  const handleUploadAadhar = async () => {
    try {
      const result = await DocumentPicker.getDocumentAsync({
        type: ['image/*', 'application/pdf'],
        copyToCacheDirectory: true
      });

      if (result.canceled || !result.assets || result.assets.length === 0) {
        return;
      }

      const file = result.assets[0];
      
      // Check file size (approx 5MB)
      if (file.size > 5 * 1024 * 1024) {
        Alert.alert('File too large', 'Please select a file smaller than 5MB.');
        return;
      }

      setIsUploadingAadhar(true);

      const formData = new FormData();
      formData.append('document', {
        uri: file.uri,
        name: file.name,
        type: file.mimeType || 'application/octet-stream'
      });
      formData.append('token', token); // Fallback for servers that strip Authorization headers

      const response = await axios.post(
        'http://192.168.1.19/renter-system/api/resident/upload_aadhar.php',
        formData,
        {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'multipart/form-data'
          }
        }
      );

      if (response.data.status === 'success') {
        Alert.alert('Success', 'Aadhar document uploaded successfully!');
        fetchProfileData();
      } else {
        Alert.alert('Upload Failed', response.data.message);
      }
    } catch (error) {
      console.log('Upload error:', error);
      Alert.alert('Error', 'Failed to upload document.');
    } finally {
      setIsUploadingAadhar(false);
    }
  };

  if (isLoading && !refreshing) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#6366F1" />
        <Text style={{ marginTop: 12, color: '#64748b' }}>Loading profile...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" translucent backgroundColor="transparent" />
      
      <ScrollView 
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor="#FFFFFF" />}
        contentContainerStyle={{ paddingBottom: 100 }}
      >
        {/* HEADER SECTION */}
        <View style={styles.headerContainer}>
          <LinearGradient
            colors={['#5E4BF5', '#765EFB']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={styles.headerGradient}
          />
          
          {/* Wave Overlay Fake via Circle */}
          <View style={styles.waveBase} />
          <View style={styles.waveOverlay} />

          <SafeAreaView style={{ flex: 1 }}>
            <View style={styles.topNav}>
              <View>
                <Text style={styles.headerTitle}>My Profile</Text>
                <Text style={styles.headerSubtitle}>View and manage your profile information</Text>
              </View>

              <TouchableOpacity style={styles.notificationBtn} onPress={() => navigation.navigate('Notifications')}>
                <Ionicons name="notifications" size={24} color="#FFFFFF" />
                <View style={styles.notificationDot}><Text style={styles.notificationDotText}>3</Text></View>
              </TouchableOpacity>
            </View>
          </SafeAreaView>
        </View>

        {/* PROFILE CARD (Overlaps Header) */}
        <View style={styles.profileCardWrapper}>
          <View style={styles.profileCard}>
              <View style={styles.profileImageContainer}>
                <Image 
                  source={{ uri: profile.profile_pic ? `http://192.168.1.19/renter-system/${profile.profile_pic}` : 'https://images.unsplash.com/photo-1599566150163-29194dcaad36?auto=format&fit=facearea&facepad=2&w=100&h=100&q=80' }} 
                  style={styles.profilePic} 
                />
                <TouchableOpacity style={styles.editPicBtn}>
                  <Ionicons name="camera-outline" size={16} color="#FFFFFF" />
                </TouchableOpacity>
              </View>

              <View style={styles.profileInfoContainer}>
                <Text style={styles.userNameText} numberOfLines={1}>{profile.name || profile.username || 'Resident Name'}</Text>
                <View style={styles.roomBadge}>
                  <Ionicons name="home-outline" size={12} color="#5E4BF5" />
                  <Text style={styles.roomBadgeText}>Room {profile.room_no || 'N/A'}</Text>
                </View>
                
                <View style={styles.contactRow}>
                  <Ionicons name="call-outline" size={14} color="#64748B" />
                  <Text style={styles.contactText} numberOfLines={1}>{profile.phone || '+91 98765 43210'}</Text>
                </View>
                
                <View style={styles.contactRow}>
                  <Ionicons name="mail-outline" size={14} color="#64748B" />
                  <Text style={styles.contactText} numberOfLines={1}>{profile.email || 'email@example.com'}</Text>
                </View>
              </View>

            <TouchableOpacity style={styles.editProfileBtn} onPress={() => setIsEditModalVisible(true)}>
              <MaterialCommunityIcons name="pencil" size={14} color="#5E4BF5" />
              <Text style={styles.editProfileText}>Edit Profile</Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* PROFILE DETAILS */}
        <View style={styles.detailsContainer}>
          
          <Text style={styles.sectionTitle}>Personal Information</Text>
          <View style={styles.card}>
            <ProfileRow icon="checkmark-circle-outline" label="Account Status" value="Active" valueColor="#10B981" />
            <ProfileRow icon="person-outline" label="Username" value={profile.username} />
            <ProfileRow icon="call-outline" label="Phone Number" value={profile.phone || 'Not Provided'} />
            <ProfileRow icon="logo-whatsapp" label="WhatsApp No." value={profile.whatsapp || 'Not Provided'} />
            <ProfileRow icon="mail-outline" label="Email Address" value={profile.email || 'Not Provided'} />
            <ProfileRow icon="calendar-outline" label="Joined Date" value={profile.created_at ? new Date(profile.created_at).toLocaleDateString() : 'N/A'} noBorder />
          </View>

          <Text style={styles.sectionTitle}>Financial Summary</Text>
          <View style={styles.card}>
            <ProfileRow icon="wallet-outline" label="Monthly Rent" value={`₹ ${(profile.fixed_rent || 0).toLocaleString('en-IN')}`} />
            <ProfileRow icon="build-outline" label="Fixed Maintenance" value={`₹ ${(profile.fixed_maintenance || 0).toLocaleString('en-IN')}`} />
            <ProfileRow icon="shield-checkmark-outline" label="Advance / Security Deposit" value={`₹ ${(profile.advance_payment || 0).toLocaleString('en-IN')}`} noBorder />
          </View>

          <Text style={styles.sectionTitle}>Documents & Identity</Text>
          <View style={styles.card}>
            <View style={styles.profileRow}>
              <View style={styles.profileRowLeft}>
                <Ionicons name="card-outline" size={20} color="#94A3B8" />
                <Text style={styles.profileRowLabel}>Aadhar / ID Proof</Text>
              </View>
              {isUploadingAadhar ? <ActivityIndicator size="small" color="#5E4BF5" /> : <TouchableOpacity onPress={handleUploadAadhar} style={{flexDirection: 'row', alignItems: 'center'}}><Text style={[styles.profileRowValue, { color: profile.aadhaar_file ? '#10B981' : '#5E4BF5' }]}>{profile.aadhaar_file ? 'Uploaded' : 'Upload'}</Text>{profile.aadhaar_file ? null : <Ionicons name="cloud-upload-outline" size={16} color="#5E4BF5" style={{marginLeft: 4}} />}</TouchableOpacity>}
            </View>
            <View style={styles.profileRowBorder} />
            <ProfileRow icon="document-text-outline" label="Rental Agreement" value="View Document" valueColor="#5E4BF5" noBorder />
          </View>

          {/* ACTIONS */}
          <Text style={styles.sectionTitle}>Account Actions</Text>
          <View style={styles.card}>
            <TouchableOpacity style={styles.actionRow} onPress={() => setIsPasswordModalVisible(true)}>
              <View style={styles.actionRowLeft}>
                <View style={[styles.actionIconBg, { backgroundColor: '#F3E8FF' }]}>
                  <Ionicons name="lock-closed-outline" size={20} color="#8B5CF6" />
                </View>
                <Text style={styles.actionLabel}>Change Password</Text>
              </View>
              <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
            </TouchableOpacity>

            <TouchableOpacity style={[styles.actionRow, { borderBottomWidth: 0 }]} onPress={handleLogout}>
              <View style={styles.actionRowLeft}>
                <View style={[styles.actionIconBg, { backgroundColor: '#FEF2F2' }]}>
                  <Ionicons name="log-out-outline" size={20} color="#EF4444" />
                </View>
                <Text style={[styles.actionLabel, { color: '#EF4444' }]}>Logout</Text>
              </View>
              <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
            </TouchableOpacity>
          </View>

        </View>
      </ScrollView>

      {/* CUSTOM BOTTOM NAVIGATION BAR */}
      <View style={styles.bottomTabBar}>
        <BottomTabBtn icon="home-outline" label="Home" onPress={() => navigation.navigate('ResidentDashboard', { token })} />
        <BottomTabBtn icon="document-text-outline" label="Bills" onPress={() => navigation.navigate('ResidentBills', { token })} />
        <View style={{ width: 60 }} />
        <BottomTabBtn icon="chatbubbles-outline" label="Queries" onPress={() => navigation.navigate('ResidentQueries', { token })} />
        <BottomTabBtn icon="person" label="Profile" active />

        {/* FLOATING PAY NOW BUTTON */}
        <TouchableOpacity 
          style={styles.fabBtn} 
          activeOpacity={0.8}
          onPress={() => navigation.navigate('ResidentPay', { token })}
        >
          <LinearGradient
            colors={['#7E5EFC', '#4F38F9']}
            style={styles.fabGradient}
          >
            <MaterialCommunityIcons name="qrcode-scan" size={26} color="#FFFFFF" />
          </LinearGradient>
          <Text style={styles.fabLabel}>Pay Now</Text>
        </TouchableOpacity>
      </View>

      {/* EDIT PROFILE MODAL */}
      <Modal visible={isEditModalVisible} transparent animationType="slide">
        <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Edit Profile</Text>
              <TouchableOpacity onPress={() => setIsEditModalVisible(false)} style={styles.closeBtn}>
                <Ionicons name="close" size={24} color="#64748B" />
              </TouchableOpacity>
            </View>

            <ScrollView showsVerticalScrollIndicator={false}>
              <Text style={styles.inputLabel}>Full Name</Text>
              <View style={styles.inputContainer}>
                <Ionicons name="person-outline" size={20} color="#94A3B8" />
                <TextInput 
                  style={styles.textInput} 
                  value={editForm.name} 
                  onChangeText={(val) => setEditForm({...editForm, name: val})}
                  placeholder="Enter your name" 
                />
              </View>

              <Text style={styles.inputLabel}>Phone Number</Text>
              <View style={styles.inputContainer}>
                <Ionicons name="call-outline" size={20} color="#94A3B8" />
                <TextInput 
                  style={styles.textInput} 
                  value={editForm.phone} 
                  onChangeText={(val) => setEditForm({...editForm, phone: val})}
                  placeholder="Enter phone number" 
                  keyboardType="phone-pad"
                />
              </View>

              <Text style={styles.inputLabel}>WhatsApp Number</Text>
              <View style={styles.inputContainer}>
                <Ionicons name="logo-whatsapp" size={20} color="#94A3B8" />
                <TextInput 
                  style={styles.textInput} 
                  value={editForm.whatsapp} 
                  onChangeText={(val) => setEditForm({...editForm, whatsapp: val})}
                  placeholder="Enter WhatsApp number" 
                  keyboardType="phone-pad"
                />
              </View>

              <Text style={styles.inputLabel}>Email Address</Text>
              <View style={styles.inputContainer}>
                <Ionicons name="mail-outline" size={20} color="#94A3B8" />
                <TextInput 
                  style={styles.textInput} 
                  value={editForm.email} 
                  onChangeText={(val) => setEditForm({...editForm, email: val})}
                  placeholder="Enter email address" 
                  keyboardType="email-address"
                  autoCapitalize="none"
                />
              </View>

              <TouchableOpacity style={styles.saveBtn} onPress={handleSaveProfile} disabled={isSaving}>
                {isSaving ? <ActivityIndicator color="#FFF" /> : <Text style={styles.saveBtnText}>Save Changes</Text>}
              </TouchableOpacity>
            </ScrollView>
          </View>
        </KeyboardAvoidingView>
      </Modal>

      {/* CHANGE PASSWORD MODAL */}
      <Modal visible={isPasswordModalVisible} transparent animationType="slide">
        <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Change Password</Text>
              <TouchableOpacity onPress={() => setIsPasswordModalVisible(false)} style={styles.closeBtn}>
                <Ionicons name="close" size={24} color="#64748B" />
              </TouchableOpacity>
            </View>

            <ScrollView showsVerticalScrollIndicator={false}>
              <Text style={styles.inputLabel}>Current Password</Text>
              <View style={styles.inputContainer}>
                <Ionicons name="lock-closed-outline" size={20} color="#94A3B8" />
                <TextInput 
                  style={styles.textInput} 
                  value={passwordForm.currentPassword} 
                  onChangeText={(val) => setPasswordForm({...passwordForm, currentPassword: val})}
                  placeholder="Enter current password" 
                  secureTextEntry={!showPasswords}
                />
              </View>

              <Text style={styles.inputLabel}>New Password</Text>
              <View style={styles.inputContainer}>
                <Ionicons name="key-outline" size={20} color="#94A3B8" />
                <TextInput 
                  style={styles.textInput} 
                  value={passwordForm.newPassword} 
                  onChangeText={(val) => setPasswordForm({...passwordForm, newPassword: val})}
                  placeholder="Enter new password (min 6 chars)" 
                  secureTextEntry={!showPasswords}
                />
              </View>

              <Text style={styles.inputLabel}>Confirm New Password</Text>
              <View style={styles.inputContainer}>
                <Ionicons name="checkmark-circle-outline" size={20} color="#94A3B8" />
                <TextInput 
                  style={styles.textInput} 
                  value={passwordForm.confirmPassword} 
                  onChangeText={(val) => setPasswordForm({...passwordForm, confirmPassword: val})}
                  placeholder="Confirm new password" 
                  secureTextEntry={!showPasswords}
                />
              </View>
              
              <TouchableOpacity style={{flexDirection: 'row', alignItems: 'center', marginTop: 16}} onPress={() => setShowPasswords(!showPasswords)}>
                <Ionicons name={showPasswords ? "eye-off-outline" : "eye-outline"} size={20} color="#64748B" />
                <Text style={{marginLeft: 8, color: '#64748B', fontWeight: '500'}}>Show Passwords</Text>
              </TouchableOpacity>

              <TouchableOpacity style={styles.saveBtn} onPress={handleChangePassword} disabled={isChangingPassword}>
                {isChangingPassword ? <ActivityIndicator color="#FFF" /> : <Text style={styles.saveBtnText}>Change Password</Text>}
              </TouchableOpacity>
            </ScrollView>
          </View>
        </KeyboardAvoidingView>
      </Modal>

    </View>
  );
}

// Components
const ProfileRow = ({ icon, label, value, valueColor = '#1E293B', noBorder = false }) => (
  <View style={[styles.profileRow, !noBorder && styles.profileRowBorder]}>
    <View style={styles.profileRowLeft}>
      <Ionicons name={icon} size={20} color="#64748B" />
      <Text style={styles.profileRowLabel}>{label}</Text>
    </View>
    <Text style={[styles.profileRowValue, { color: valueColor }]}>{value}</Text>
  </View>
);

const BottomTabBtn = ({ icon, label, active, onPress }) => (
  <TouchableOpacity style={styles.tabBtn} onPress={onPress}>
    <Ionicons name={icon} size={24} color={active ? '#5b42f3' : '#94A3B8'} />
    <Text style={[styles.tabLabel, active && styles.tabLabelActive]}>{label}</Text>
  </TouchableOpacity>
);

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#FCFBFC' },
  loadingContainer: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#FCFBFC' },
  
  headerContainer: {
    height: 300,
    width: '100%',
    position: 'relative',
    backgroundColor: '#FCFBFC',
    overflow: 'hidden'
  },
  headerGradient: {
    height: 300,
    width: '100%',
    position: 'absolute',
    top: 0,
  },
  waveBase: {
    position: 'absolute',
    bottom: -80,
    left: -100,
    width: width * 1.5,
    height: 200,
    backgroundColor: '#8674FA',
    borderRadius: width,
    opacity: 0.3,
  },
  waveOverlay: {
    position: 'absolute',
    bottom: -120,
    left: -50,
    width: width * 1.5,
    height: 200,
    backgroundColor: '#FCFBFC',
    borderRadius: width,
    transform: [{ rotate: '-5deg' }]
  },
  topNav: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    width: width,
    paddingHorizontal: 24,
    paddingTop: 80, // Increased to push title downwards
    zIndex: 10
  },
  headerTitle: { color: '#FFFFFF', fontSize: 24, fontWeight: '700', marginBottom: 4 },
  headerSubtitle: { color: 'rgba(255,255,255,0.8)', fontSize: 13, fontWeight: '500' },
  
  notificationBtn: { width: 44, height: 44, borderRadius: 14, borderWidth: 1, borderColor: 'rgba(255,255,255,0.5)', justifyContent: 'center', alignItems: 'center' },
  notificationDot: { position: 'absolute', top: -6, right: -6, backgroundColor: '#EF4444', borderRadius: 10, minWidth: 20, height: 20, justifyContent: 'center', alignItems: 'center', borderWidth: 2, borderColor: '#5E4BF5' },
  notificationDotText: { color: '#FFFFFF', fontSize: 10, fontWeight: 'bold' },

  profileCardWrapper: {
    paddingHorizontal: 24,
    marginTop: -140, // Overlaps wave (shifted further up)
    marginBottom: 10,
    zIndex: 20,
  },
  profileCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    padding: 20,
    flexDirection: 'row',
    alignItems: 'center',
    shadowColor: '#000', shadowOffset: { width: 0, height: 8 }, shadowOpacity: 0.08, shadowRadius: 15, elevation: 10,
    position: 'relative'
  },
  profileImageContainer: { position: 'relative', marginRight: 16 },
  profilePic: { width: 90, height: 90, borderRadius: 45, borderWidth: 3, borderColor: '#FDE4ED' }, // subtle pink border
  editPicBtn: { position: 'absolute', bottom: 0, right: 0, backgroundColor: '#5E4BF5', width: 28, height: 28, borderRadius: 14, justifyContent: 'center', alignItems: 'center', borderWidth: 2, borderColor: '#FFFFFF' },
  
  profileInfoContainer: { flex: 1, justifyContent: 'center', paddingRight: 40 }, // padding right to avoid overlap with edit button if it was there, but edit button is top right
  userNameText: { color: '#0F172A', fontSize: 20, fontWeight: '800', marginBottom: 6, paddingRight: 80 }, // padding to avoid edit button overlap
  roomBadge: { backgroundColor: '#F3E8FF', flexDirection: 'row', alignItems: 'center', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 12, alignSelf: 'flex-start', marginBottom: 10 },
  roomBadgeText: { color: '#5E4BF5', fontSize: 12, fontWeight: '700', marginLeft: 4 },
  
  contactRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 6 },
  contactText: { flex: 1, fontSize: 12, color: '#0F172A', marginLeft: 8, fontWeight: '500' },
  
  editProfileBtn: { position: 'absolute', top: 20, right: 20, flexDirection: 'row', alignItems: 'center', paddingHorizontal: 10, paddingVertical: 6, borderWidth: 1, borderColor: '#DDD6FE', borderRadius: 10 },
  editProfileText: { color: '#5E4BF5', fontSize: 11, fontWeight: '600', marginLeft: 4 },
  
  detailsContainer: {
    paddingHorizontal: 24,
    marginTop: -10, // Pull details container closer to the profile card
    paddingBottom: 40
  },
  sectionTitle: { fontSize: 16, fontWeight: '700', color: '#1E293B', marginTop: 12, marginBottom: 4, marginLeft: 4 },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    padding: 16,
    shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.05, shadowRadius: 10, elevation: 4,
  },
  profileRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 14 },
  profileRowBorder: { borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  profileRowLeft: { flexDirection: 'row', alignItems: 'center' },
  profileRowLabel: { fontSize: 14, color: '#64748B', marginLeft: 12, fontWeight: '500' },
  profileRowValue: { fontSize: 14, fontWeight: '600' },

  actionRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  actionRowLeft: { flexDirection: 'row', alignItems: 'center' },
  actionIconBg: { width: 40, height: 40, borderRadius: 12, justifyContent: 'center', alignItems: 'center', marginRight: 12 },
  actionLabel: { fontSize: 15, fontWeight: '600', color: '#1E293B' },

  modalOverlay: { flex: 1, backgroundColor: 'rgba(15, 23, 42, 0.6)', justifyContent: 'flex-end' },
  modalContent: { backgroundColor: '#FFFFFF', borderTopLeftRadius: 30, borderTopRightRadius: 30, padding: 24, paddingBottom: Platform.OS === 'ios' ? 40 : 24, maxHeight: '85%' },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 },
  modalTitle: { fontSize: 20, fontWeight: '800', color: '#0F172A' },
  closeBtn: { backgroundColor: '#F1F5F9', width: 36, height: 36, borderRadius: 18, justifyContent: 'center', alignItems: 'center' },
  
  inputLabel: { fontSize: 13, fontWeight: '600', color: '#475569', marginBottom: 8, marginLeft: 4 },
  inputContainer: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#F8FAFC', borderWidth: 1, borderColor: '#E2E8F0', borderRadius: 14, paddingHorizontal: 16, height: 52, marginBottom: 16 },
  textInput: { flex: 1, height: '100%', marginLeft: 10, fontSize: 15, color: '#1E293B' },
  
  saveBtn: { backgroundColor: '#5E4BF5', borderRadius: 16, height: 56, justifyContent: 'center', alignItems: 'center', marginTop: 10, shadowColor: '#5E4BF5', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.3, shadowRadius: 8, elevation: 4 },
  saveBtnText: { color: '#FFFFFF', fontSize: 16, fontWeight: '700' },

  bottomTabBar: {
    position: 'absolute', bottom: 0, left: 0, right: 0, height: 80, backgroundColor: '#FFFFFF',
    flexDirection: 'row', justifyContent: 'space-around', alignItems: 'center',
    shadowColor: '#000', shadowOffset: { width: 0, height: -4 }, shadowOpacity: 0.05, shadowRadius: 10, elevation: 15,
    borderTopLeftRadius: 24, borderTopRightRadius: 24, paddingBottom: 10
  },
  tabBtn: { alignItems: 'center', justifyContent: 'center', width: 60 },
  tabLabel: { fontSize: 10, color: '#94A3B8', marginTop: 4, fontWeight: '600' },
  tabLabelActive: { color: '#5b42f3' },
  fabBtn: { position: 'absolute', bottom: 25, left: width / 2 - 35, alignItems: 'center' },
  fabGradient: { width: 64, height: 64, borderRadius: 32, justifyContent: 'center', alignItems: 'center', shadowColor: '#5b42f3', shadowOffset: { width: 0, height: 8 }, shadowOpacity: 0.4, shadowRadius: 12, elevation: 8, marginBottom: 4 },
  fabLabel: { fontSize: 11, fontWeight: '700', color: '#5b42f3' }
});
