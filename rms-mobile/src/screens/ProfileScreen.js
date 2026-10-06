import React, { useState, useEffect } from 'react';
import { 
  View, Text, StyleSheet, ScrollView, TouchableOpacity, 
  SafeAreaView, TextInput, ActivityIndicator, StatusBar, Platform, Alert, Dimensions 
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import Svg, { Path, Defs, LinearGradient as SvgGradient, Stop } from 'react-native-svg';
import axios from 'axios';

const { width } = Dimensions.get('window');

const ProfileScreen = ({ navigation }) => {
  const [profile, setProfile] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  // Password state
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [isUpdating, setIsUpdating] = useState(false);
  const [showCurrent, setShowCurrent] = useState(false);
  const [showNew, setShowNew] = useState(false);

  useEffect(() => {
    fetchProfile();
  }, []);

  const fetchProfile = async () => {
    try {
      const response = await axios.get('http://192.168.1.19/renter-system/api/admin/get_profile_data.php');
      if (response.data.status === 'success') {
        setProfile(response.data.data);
      }
    } catch (error) {
      console.log("Error fetching profile", error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleUpdatePassword = async () => {
    if (!currentPassword || !newPassword || !confirmPassword) {
      Alert.alert('Error', 'Please fill all fields');
      return;
    }
    if (newPassword !== confirmPassword) {
      Alert.alert('Error', 'New passwords do not match');
      return;
    }

    setIsUpdating(true);
    try {
      const res = await axios.post('http://192.168.1.19/renter-system/api/admin/mobile_update_password.php', {
        current_password: currentPassword,
        new_password: newPassword
      });

      if (res.data.status === 'success') {
        Alert.alert('Success', 'Password updated successfully!');
        setCurrentPassword('');
        setNewPassword('');
        setConfirmPassword('');
      } else {
        Alert.alert('Error', res.data.message || 'Update failed');
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'Network error occurred';
      Alert.alert('Error', msg);
    } finally {
      setIsUpdating(false);
    }
  };

  const handleLogout = () => {
    Alert.alert(
      "Log Out",
      "Are you sure you want to log out?",
      [
        { text: "Cancel", style: "cancel" },
        { 
          text: "Log Out", 
          style: "destructive",
          onPress: () => {
            navigation.reset({
              index: 0,
              routes: [{ name: 'Login' }],
            });
          }
        }
      ]
    );
  };

  if (isLoading) {
    return (
      <View style={styles.loaderCenter}>
        <ActivityIndicator size="large" color="#06B6D4" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />
      
      {/* Dynamic Curved Header Background */}
      <View style={styles.headerBackground}>
        <Svg width={width} height={420} viewBox={`0 0 ${width} 420`}>
          <Defs>
            <SvgGradient id="profileGrad" x1="0" y1="0" x2="1" y2="1">
              <Stop offset="0" stopColor="#0F172A" stopOpacity="1" />
              <Stop offset="1" stopColor="#334155" stopOpacity="1" />
            </SvgGradient>
          </Defs>
          <Path 
            d={`M 0 0 L ${width} 0 L ${width} 280 Q ${width/2} 420 0 280 Z`}
            fill="url(#profileGrad)" 
          />
        </Svg>
        
        {/* Subtle decorative circles */}
        <View style={styles.decoCircle1} />
        <View style={styles.decoCircle2} />
      </View>

      <SafeAreaView style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
          
          {/* Top Bar (Back button, Title, Refresh) */}
          <View style={styles.topBar}>
            <TouchableOpacity onPress={() => navigation.goBack()} style={styles.refreshBtn}>
              <Ionicons name="arrow-back" size={24} color="#fff" />
            </TouchableOpacity>
            <Text style={styles.topBarTitle}>Account</Text>
            <TouchableOpacity onPress={fetchProfile} style={styles.refreshBtn}>
              <Ionicons name="sync" size={22} color="#fff" />
            </TouchableOpacity>
          </View>

          {/* Floating Profile Section */}
          <View style={styles.profileFloat}>
            <View style={styles.avatarWrapper}>
              <LinearGradient colors={['#06B6D4', '#3B82F6']} style={styles.avatarBorder}>
                <View style={styles.avatarInner}>
                  <Ionicons name="shield-half" size={42} color="#06B6D4" />
                </View>
              </LinearGradient>
              <View style={styles.verifiedBadge}>
                <Ionicons name="checkmark-circle" size={18} color="#10B981" />
              </View>
            </View>
            <Text style={styles.profileName}>@{profile?.username || 'admin'}</Text>
            <Text style={styles.profileRole}>{profile?.role || 'System Administrator'}</Text>
          </View>

          {/* Modern Overlapping KPIs */}
          <View style={styles.kpiContainer}>
            <View style={styles.kpiCard}>
              <View style={styles.kpiIconTop}>
                <Ionicons name="layers" size={24} color="#6366F1" />
              </View>
              <Text style={styles.kpiValueBig}>{profile?.total_bills}</Text>
              <Text style={styles.kpiLabelSm}>Generated Bills</Text>
            </View>

            <View style={styles.kpiCard}>
              <View style={styles.kpiIconTop}>
                <Ionicons name="wallet" size={24} color="#F59E0B" />
              </View>
              <Text style={styles.kpiValueBig}>₹{profile?.total_pending?.toLocaleString('en-IN')}</Text>
              <Text style={styles.kpiLabelSm}>Pending Dues</Text>
            </View>
          </View>

          {/* Minimalist Security Settings */}
          <View style={styles.settingsSection}>
            <Text style={styles.sectionTitle}>Security Settings</Text>

            <View style={styles.glassForm}>
              <View style={styles.modernInputBox}>
                <Ionicons name="lock-closed-outline" size={20} color="#64748B" style={styles.iconFixed} />
                <TextInput 
                  style={styles.modernInput}
                  placeholder="Current Password"
                  placeholderTextColor="#94A3B8"
                  secureTextEntry={!showCurrent}
                  value={currentPassword}
                  onChangeText={setCurrentPassword}
                />
                <TouchableOpacity onPress={() => setShowCurrent(!showCurrent)} style={styles.eyeIconFixed}>
                  <Ionicons name={showCurrent ? "eye-off-outline" : "eye-outline"} size={20} color="#94A3B8" />
                </TouchableOpacity>
              </View>

              <View style={styles.divider} />

              <View style={styles.modernInputBox}>
                <Ionicons name="key-outline" size={20} color="#64748B" style={styles.iconFixed} />
                <TextInput 
                  style={styles.modernInput}
                  placeholder="New Password (Min 6)"
                  placeholderTextColor="#94A3B8"
                  secureTextEntry={!showNew}
                  value={newPassword}
                  onChangeText={setNewPassword}
                />
                <TouchableOpacity onPress={() => setShowNew(!showNew)} style={styles.eyeIconFixed}>
                  <Ionicons name={showNew ? "eye-off-outline" : "eye-outline"} size={20} color="#94A3B8" />
                </TouchableOpacity>
              </View>

              <View style={styles.divider} />

              <View style={styles.modernInputBox}>
                <Ionicons name="shield-checkmark-outline" size={20} color="#64748B" style={styles.iconFixed} />
                <TextInput 
                  style={styles.modernInput}
                  placeholder="Confirm New Password"
                  placeholderTextColor="#94A3B8"
                  secureTextEntry={!showNew}
                  value={confirmPassword}
                  onChangeText={setConfirmPassword}
                />
              </View>
            </View>

            <TouchableOpacity activeOpacity={0.8} style={styles.updateAction} onPress={handleUpdatePassword} disabled={isUpdating}>
              <LinearGradient colors={['#0F172A', '#1E293B']} style={styles.updateGradient}>
                {isUpdating ? (
                  <ActivityIndicator color="#06B6D4" />
                ) : (
                  <>
                    <Text style={styles.updateText}>Update Credentials</Text>
                    <Ionicons name="arrow-forward" size={18} color="#06B6D4" />
                  </>
                )}
              </LinearGradient>
            </TouchableOpacity>
          </View>

          {/* Danger Zone Logout */}
          <TouchableOpacity style={styles.logoutWrapper} onPress={handleLogout}>
            <View style={styles.logoutIconBg}>
              <Ionicons name="log-out" size={22} color="#EF4444" />
            </View>
            <Text style={styles.logoutText}>Terminate Session</Text>
          </TouchableOpacity>

        </ScrollView>
      </SafeAreaView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  loaderCenter: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#0F172A' },
  
  headerBackground: {
    position: 'absolute',
    top: 0, left: 0, right: 0,
    height: 420,
    zIndex: 0
  },
  decoCircle1: {
    position: 'absolute', top: -20, right: -40,
    width: 150, height: 150, borderRadius: 75,
    backgroundColor: 'rgba(6, 182, 212, 0.15)',
  },
  decoCircle2: {
    position: 'absolute', top: 80, left: -30,
    width: 100, height: 100, borderRadius: 50,
    backgroundColor: 'rgba(99, 102, 241, 0.1)',
  },

  scrollContent: {
    paddingBottom: 40,
    paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight + 30 : 40,
  },
  
  topBar: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingHorizontal: 24, marginBottom: 10
  },
  topBarTitle: { fontSize: 16, fontWeight: '700', color: '#fff', letterSpacing: 1, textTransform: 'uppercase' },
  refreshBtn: { padding: 8 },

  profileFloat: {
    alignItems: 'center',
    marginTop: 10,
    marginBottom: 40
  },
  avatarWrapper: {
    position: 'relative', marginBottom: 16,
    shadowColor: '#06B6D4', shadowOffset: { width: 0, height: 8 }, shadowOpacity: 0.4, shadowRadius: 16, elevation: 10
  },
  avatarBorder: {
    width: 100, height: 100, borderRadius: 50,
    padding: 3, // creates the border thickness
  },
  avatarInner: {
    flex: 1, backgroundColor: '#0F172A', borderRadius: 50,
    justifyContent: 'center', alignItems: 'center'
  },
  verifiedBadge: {
    position: 'absolute', bottom: 2, right: 2,
    backgroundColor: '#fff', borderRadius: 12, padding: 2,
    shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.2, shadowRadius: 4, elevation: 4
  },
  profileName: { fontSize: 26, fontWeight: '800', color: '#fff', marginBottom: 4 },
  profileRole: { fontSize: 14, color: '#94A3B8', fontWeight: '500', textTransform: 'uppercase', letterSpacing: 1 },

  kpiContainer: {
    flexDirection: 'row', justifyContent: 'space-between',
    paddingHorizontal: 20,
    marginTop: 10, // Shifted downwards to overlap the new curve beautifully
    marginBottom: 32
  },
  kpiCard: {
    flex: 1, backgroundColor: '#fff',
    borderRadius: 24, padding: 20, marginHorizontal: 6,
    shadowColor: '#64748B', shadowOffset: { width: 0, height: 12 }, shadowOpacity: 0.08, shadowRadius: 24, elevation: 8,
    alignItems: 'center'
  },
  kpiIconTop: {
    width: 48, height: 48, borderRadius: 24, backgroundColor: '#F8FAFC',
    justifyContent: 'center', alignItems: 'center', marginBottom: 12,
    borderWidth: 1, borderColor: '#F1F5F9'
  },
  kpiValueBig: { fontSize: 24, fontWeight: '800', color: '#0F172A', marginBottom: 4 },
  kpiLabelSm: { fontSize: 12, color: '#64748B', fontWeight: '600' },

  settingsSection: {
    paddingHorizontal: 24,
    marginBottom: 20
  },
  sectionTitle: { fontSize: 14, fontWeight: '700', color: '#94A3B8', textTransform: 'uppercase', letterSpacing: 1, marginBottom: 16, marginLeft: 4 },
  
  glassForm: {
    backgroundColor: '#fff',
    borderRadius: 24,
    paddingVertical: 8,
    shadowColor: '#64748B', shadowOffset: { width: 0, height: 8 }, shadowOpacity: 0.05, shadowRadius: 16, elevation: 5,
    borderWidth: 1, borderColor: '#F1F5F9'
  },
  modernInputBox: {
    flexDirection: 'row', alignItems: 'center',
    height: 60, paddingHorizontal: 20
  },
  iconFixed: { width: 28 },
  modernInput: { flex: 1, fontSize: 15, color: '#0F172A', fontWeight: '500' },
  eyeIconFixed: { padding: 8, marginRight: -8 },
  divider: { height: 1, backgroundColor: '#F1F5F9', marginLeft: 48 },

  updateAction: { marginTop: 24, shadowColor: '#0F172A', shadowOffset: { width: 0, height: 8 }, shadowOpacity: 0.2, shadowRadius: 16, elevation: 8 },
  updateGradient: {
    flexDirection: 'row', height: 60, borderRadius: 20,
    justifyContent: 'center', alignItems: 'center',
    paddingHorizontal: 24
  },
  updateText: { color: '#fff', fontSize: 16, fontWeight: '700', marginRight: 12 },

  logoutWrapper: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    alignSelf: 'center', marginTop: 10,
    paddingVertical: 12, paddingHorizontal: 24,
    backgroundColor: '#FEF2F2', borderRadius: 100,
    borderWidth: 1, borderColor: '#FEE2E2'
  },
  logoutIconBg: {
    width: 32, height: 32, borderRadius: 16, backgroundColor: '#fff',
    justifyContent: 'center', alignItems: 'center', marginRight: 12,
    shadowColor: '#EF4444', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.1, shadowRadius: 4, elevation: 2
  },
  logoutText: { color: '#EF4444', fontSize: 15, fontWeight: '700' }
});

export default ProfileScreen;
