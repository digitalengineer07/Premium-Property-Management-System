import React, { useState } from 'react';
import { View, Text, StyleSheet, TextInput, TouchableOpacity, SafeAreaView, Dimensions, KeyboardAvoidingView, Platform, ScrollView, Image, Alert, ActivityIndicator } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import Svg, { Path, Defs, LinearGradient as SvgGradient, Stop } from 'react-native-svg';
import { Ionicons, MaterialCommunityIcons } from '@expo/vector-icons';
import axios from 'axios';

const { width } = Dimensions.get('window');
const API_URL = 'http://192.168.1.19/renter-system/api/auth/login.php';

export default function LoginFormScreen({ route, navigation }) {
  const { role } = route.params || { role: 'admin' };
  
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  const handleLogin = async () => {
    if (!username || !password) {
      Alert.alert("Validation Error", "Please enter both username and password.");
      return;
    }

    setIsLoading(true);

    try {
      const response = await axios.post(API_URL, {
        username: username,
        password: password,
        role: role
      });

      if (response.data.status === 'success') {
        if (role === 'admin') {
          navigation.replace('AdminDashboard');
        } else {
          // Resident login successful, pass token to ResidentDashboard
          const token = response.data.token;
          navigation.replace('ResidentDashboard', { token: token });
        }
      } else {
        Alert.alert("Login Failed", response.data.message || "Invalid credentials.");
      }
    } catch (error) {
      console.error(error);
      if (error.response && error.response.data && error.response.data.message) {
        Alert.alert("Login Failed", error.response.data.message);
      } else {
        Alert.alert("Network Error", "Unable to connect to the server. Please ensure your local database is running.");
      }
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
        <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scrollContent}>
          
          {/* Header Section (Matching the Role Selector) */}
          <View style={styles.headerSection}>
            {/* Background Elements */}
            <View style={styles.bgBlobTopLeft}>
              <Svg width={width} height={350} viewBox={`0 0 ${width} 350`}>
                <Defs>
                  <SvgGradient id="waveGrad" x1="0" y1="0" x2="0" y2="1">
                    <Stop offset="0" stopColor="#6366F1" stopOpacity="1" />
                    <Stop offset="1" stopColor="#4F46E5" stopOpacity="1" />
                  </SvgGradient>
                </Defs>
                <Path 
                  d={`M 0 0 L ${width * 0.55} 0 C ${width * 0.4} 10, ${width * 0.3} 90, ${width * 0.15} 140 C ${width * 0.05} 170, 10 250, 0 300 Z`}
                  fill="url(#waveGrad)" 
                />
              </Svg>
            </View>
            <View style={styles.bgCirclePurple} />
            <View style={styles.bgCircleOrange}>
              <LinearGradient colors={['#FFEDD5', '#FED7AA']} style={{ flex: 1, borderRadius: 100 }} />
            </View>

            {/* House Illustration */}
            <View style={styles.houseImageContainer}>
              <Image 
                source={require('../../assets/final_house.jpg')} 
                style={styles.houseImage} 
              />
            </View>

            {/* Back Button */}
            <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
              <Ionicons name="arrow-back" size={20} color="#4F46E5" />
            </TouchableOpacity>

            <View style={styles.headerContent}>
              {/* Logo Box */}
              <View style={styles.logoBox}>
                <Ionicons name="home-outline" size={32} color="#6366F1" style={{ position: 'absolute' }} />
                <Ionicons name="home-outline" size={32} color="#06B6D4" style={{ position: 'absolute', opacity: 0.5, marginLeft: 2 }} />
              </View>

              {/* Title */}
              <Text style={styles.titleLarge}>Welcome Back!</Text>
              <Text style={styles.subtitle}>
                Sign in to your <Text style={styles.subtitleHighlight}>{role === 'admin' ? 'Admin' : 'Resident'}</Text> account
              </Text>
            </View>
          </View>

          {/* Form Card */}
          <View style={styles.formCard}>
            
            {/* Role Indicator */}
            <View style={styles.roleIndicator}>
              <MaterialCommunityIcons 
                name={role === 'admin' ? "shield-account-outline" : "account-outline"} 
                size={20} 
                color="#6366F1" 
              />
              <Text style={styles.roleIndicatorText}>{role === 'admin' ? 'Administrator Login' : 'Resident Login'}</Text>
            </View>

            {/* Username Input */}
            <View style={styles.inputContainer}>
              <Text style={styles.inputLabel}>Username or Email</Text>
              <View style={styles.inputBox}>
                <MaterialCommunityIcons name="email-outline" size={20} color="#64748b" style={styles.inputIcon} />
                <TextInput
                  style={styles.input}
                  placeholder={`Enter your ${role} username`}
                  placeholderTextColor="#94a3b8"
                  value={username}
                  onChangeText={setUsername}
                  autoCapitalize="none"
                />
              </View>
            </View>

            {/* Password Input */}
            <View style={styles.inputContainer}>
              <Text style={styles.inputLabel}>Password</Text>
              <View style={styles.inputBox}>
                <MaterialCommunityIcons name="lock-outline" size={20} color="#64748b" style={styles.inputIcon} />
                <TextInput
                  style={styles.input}
                  placeholder="Enter your password"
                  placeholderTextColor="#94a3b8"
                  secureTextEntry={!showPassword}
                  value={password}
                  onChangeText={setPassword}
                />
                <TouchableOpacity onPress={() => setShowPassword(!showPassword)}>
                  <MaterialCommunityIcons name={showPassword ? "eye-outline" : "eye-off-outline"} size={20} color="#64748b" />
                </TouchableOpacity>
              </View>
            </View>

            {/* Forgot Password */}
            <TouchableOpacity style={styles.forgotPassword}>
              <Text style={styles.forgotPasswordText}>Forgot Password?</Text>
            </TouchableOpacity>

            {/* Sign In Button */}
            <TouchableOpacity activeOpacity={0.8} onPress={handleLogin} style={{ marginTop: 8 }} disabled={isLoading}>
              <LinearGradient colors={['#8B5CF6', '#3B82F6']} start={{x: 0, y: 0}} end={{x: 1, y: 0}} style={styles.loginButton}>
                {isLoading ? (
                  <ActivityIndicator color="#fff" size="small" />
                ) : (
                  <>
                    <Text style={styles.loginButtonText}>Sign In</Text>
                    <MaterialCommunityIcons name="login" size={20} color="#fff" style={{ marginLeft: 8 }} />
                  </>
                )}
              </LinearGradient>
            </TouchableOpacity>

          </View>

          {/* Footer Section */}
          <View style={styles.footerSection}>
            {/* Biometric Login Option */}
            <TouchableOpacity activeOpacity={0.7} style={styles.biometricButton}>
              <Ionicons name="finger-print-outline" size={22} color="#6366F1" />
              <Text style={styles.biometricText}>Use Biometric Login</Text>
            </TouchableOpacity>

            {/* Help & Signup Links */}
            <View style={styles.footerLinksRow}>
              <Text style={styles.footerText}>Need access to the portal?</Text>
              <TouchableOpacity>
                <Text style={styles.footerLink}> Contact Admin</Text>
              </TouchableOpacity>
            </View>

            {/* Settings & Support Row */}
            <View style={styles.bottomActions}>
              <TouchableOpacity style={styles.iconButton}>
                <Ionicons name="settings-outline" size={20} color="#64748b" />
                <Text style={styles.iconButtonText}>Settings</Text>
              </TouchableOpacity>
              <View style={styles.dividerDot} />
              <TouchableOpacity style={styles.iconButton}>
                <Ionicons name="help-circle-outline" size={20} color="#64748b" />
                <Text style={styles.iconButtonText}>Support</Text>
              </TouchableOpacity>
            </View>
          </View>

        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F4F7FF', // Slight off-white/blue
  },
  scrollContent: {
    paddingBottom: 40,
    flexGrow: 1,
  },
  headerSection: {
    paddingTop: 60,
    paddingHorizontal: 24,
    paddingBottom: 40,
    position: 'relative',
    overflow: 'visible',
  },
  bgBlobTopLeft: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    zIndex: 0,
  },
  bgCirclePurple: {
    position: 'absolute',
    top: 60,
    right: 120,
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(216, 180, 254, 0.4)',
  },
  bgCircleOrange: {
    position: 'absolute',
    top: 20,
    right: -20,
    width: 120,
    height: 120,
    borderRadius: 60,
    opacity: 0.8,
  },
  houseImageContainer: {
    position: 'absolute',
    top: 110,
    right: 12,
    width: 210,
    height: 210,
    zIndex: 0,
    opacity: 0.9,
    overflow: 'hidden',
    borderTopLeftRadius: 20,
    borderBottomLeftRadius: 120,
  },
  houseImage: {
    width: 210,
    height: 210,
    resizeMode: 'contain',
  },
  backButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#fff',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 20,
    zIndex: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 4,
    elevation: 3,
  },
  headerContent: {
    position: 'relative',
    zIndex: 1,
  },
  logoBox: {
    width: 64,
    height: 64,
    backgroundColor: '#fff',
    borderRadius: 16,
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08,
    shadowRadius: 8,
    elevation: 5,
    marginBottom: 20,
  },
  titleLarge: {
    fontSize: 28,
    fontWeight: '800',
    color: '#0f172a',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 14,
    color: '#64748b',
  },
  subtitleHighlight: {
    fontWeight: '700',
    color: '#6366F1',
  },
  formCard: {
    marginHorizontal: 24,
    backgroundColor: '#fff',
    borderRadius: 24,
    padding: 24,
    shadowColor: '#4F46E5',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.05,
    shadowRadius: 24,
    elevation: 5,
    zIndex: 2,
    marginTop: -20,
  },
  roleIndicator: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#F3E8FF',
    paddingVertical: 14,
    borderRadius: 12,
    marginBottom: 24,
  },
  roleIndicatorText: {
    fontSize: 13,
    fontWeight: '700',
    color: '#6366F1',
    marginLeft: 8,
  },
  inputContainer: {
    marginBottom: 20,
  },
  inputLabel: {
    fontSize: 12,
    fontWeight: '700',
    color: '#475569',
    marginBottom: 8,
  },
  inputBox: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 12,
    paddingHorizontal: 16,
    height: 52,
    backgroundColor: '#fff',
  },
  inputIcon: {
    marginRight: 12,
  },
  input: {
    flex: 1,
    fontSize: 14,
    color: '#0f172a',
  },
  forgotPassword: {
    alignSelf: 'flex-end',
    marginBottom: 24,
  },
  forgotPasswordText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#6366F1',
  },
  loginButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    height: 52,
    borderRadius: 16,
  },
  loginButtonText: {
    fontSize: 16,
    fontWeight: '700',
    color: '#fff',
  },
  footerSection: {
    alignItems: 'center',
    paddingHorizontal: 24,
    paddingTop: 8,
  },
  biometricButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#EEF2FF',
    paddingVertical: 12,
    paddingHorizontal: 24,
    borderRadius: 100,
    marginBottom: 24,
    borderWidth: 1,
    borderColor: '#E0E7FF',
  },
  biometricText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#6366F1',
    marginLeft: 8,
  },
  footerLinksRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 32,
  },
  footerText: {
    fontSize: 13,
    color: '#64748b',
  },
  footerLink: {
    fontSize: 13,
    fontWeight: '700',
    color: '#6366F1',
  },
  bottomActions: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconButton: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  iconButtonText: {
    fontSize: 12,
    fontWeight: '500',
    color: '#64748b',
    marginLeft: 4,
  },
  dividerDot: {
    width: 4,
    height: 4,
    borderRadius: 2,
    backgroundColor: '#CBD5E1',
    marginHorizontal: 16,
  }
});
