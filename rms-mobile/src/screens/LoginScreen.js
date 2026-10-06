import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ScrollView, SafeAreaView, Dimensions, Alert, Image } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import Svg, { Path, Defs, LinearGradient as SvgGradient, Stop } from 'react-native-svg';
import { Ionicons, MaterialCommunityIcons, FontAwesome5 } from '@expo/vector-icons';
import axios from 'axios';

const { width } = Dimensions.get('window');

// Set this to your local IP for Android emulator or physical device testing
// e.g., 'http://192.168.1.100/renter-system/api'
const API_URL = 'http://10.0.2.2/renter-system/api'; 

export default function LoginScreen({ navigation }) {
  const [role, setRole] = useState('resident'); // 'resident' or 'admin'
  const [loading, setLoading] = useState(false);

  const handleProceed = () => {
    navigation.navigate('LoginForm', { role });
  };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scrollContent}>
        
        {/* Header Section */}
        <View style={styles.headerSection}>
          {/* Custom SVG Background Curve */}
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

          {/* Removed Top Right House Illustration as requested */}

          <View style={styles.headerContent}>
            {/* Logo Box */}
            <View style={styles.logoBox}>
              {/* Approximating the two-tone outline home icon */}
              <Ionicons name="home-outline" size={36} color="#6366F1" style={{ position: 'absolute' }} />
              <Ionicons name="home-outline" size={36} color="#06B6D4" style={{ position: 'absolute', opacity: 0.5, marginLeft: 2 }} />
            </View>

            {/* Title */}
            <Text style={styles.titleSmall}>Madhav Kunj</Text>
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', marginBottom: 12, marginTop: 4 }}>
              <Text style={styles.titleLargeHighlight}>Rent & Bill </Text>
              <Text style={styles.titleLarge}>Management</Text>
            </View>
            
            <Text style={styles.subtitle}>
              Manage bills, track payments, and{'\n'}oversee financial records with ease.
            </Text>
          </View>
        </View>

        {/* Unified Feature Bar */}
        <View style={styles.featureBarContainer}>
          <View style={styles.featureBar}>
            
            <View style={styles.featureItem}>
              <View style={[styles.featureIconBox, { backgroundColor: '#F3E8FF' }]}>
                <MaterialCommunityIcons name="file-document-outline" size={14} color="#8B5CF6" />
              </View>
              <Text style={styles.featureText}>Easy Billing</Text>
            </View>

            <View style={styles.featureDivider} />

            <View style={styles.featureItem}>
              <View style={[styles.featureIconBox, { backgroundColor: '#DBEAFE' }]}>
                <MaterialCommunityIcons name="account-group-outline" size={14} color="#3B82F6" />
              </View>
              <Text style={styles.featureText}>Manage Residents</Text>
            </View>

            <View style={styles.featureDivider} />

            <View style={styles.featureItem}>
              <View style={[styles.featureIconBox, { backgroundColor: '#DCFCE7' }]}>
                <MaterialCommunityIcons name="chart-bar" size={14} color="#10B981" />
              </View>
              <Text style={styles.featureText}>Track Payments</Text>
            </View>

            <View style={styles.featureDivider} />

            <View style={styles.featureItem}>
              <View style={[styles.featureIconBox, { backgroundColor: '#FFEDD5' }]}>
                <MaterialCommunityIcons name="bell-outline" size={14} color="#F59E0B" />
              </View>
              <Text style={styles.featureText}>Smart Alerts</Text>
            </View>

          </View>
        </View>

        {/* Main Role Selection Card */}
        <View style={styles.mainCard}>
          <View style={styles.cardHeader}>
            <View style={{ flexDirection: 'row', alignItems: 'center' }}>
              <View style={styles.headerIconBox}>
                <MaterialCommunityIcons name="account-outline" size={20} color="#6366F1" />
              </View>
              <View>
                <Text style={styles.cardTitle}>Choose Your Role</Text>
                <Text style={styles.cardSubtitle}>Select how you want to continue</Text>
              </View>
            </View>
            <View style={styles.secureBadge}>
              <MaterialCommunityIcons name="shield-check" size={14} color="#10B981" />
              <Text style={styles.secureText}>Secure Login</Text>
            </View>
          </View>

          {/* Role Selectors - Side by Side */}
          <View style={styles.roleContainer}>
            <TouchableOpacity activeOpacity={0.8} style={{ flex: 1 }} onPress={() => setRole('resident')}>
              {role === 'resident' ? (
                <LinearGradient colors={['#6366F1', '#4F46E5']} start={{x: 0, y: 0}} end={{x: 1, y: 1}} style={styles.roleActive}>
                  <View style={styles.roleIconActiveBox}>
                    <FontAwesome5 name="user" size={14} color="#fff" />
                  </View>
                  <View style={styles.roleTextContainer}>
                    <Text style={styles.roleTitleActive}>Resident</Text>
                    <Text style={styles.roleSubtitleActive}>Access your account</Text>
                  </View>
                  <View style={styles.checkCircle}>
                    <Ionicons name="checkmark" size={12} color="#4F46E5" />
                  </View>
                </LinearGradient>
              ) : (
                <View style={styles.roleInactive}>
                  <View style={styles.roleIconInactiveBox}>
                    <FontAwesome5 name="user" size={14} color="#64748b" />
                  </View>
                  <View style={styles.roleTextContainer}>
                    <Text style={styles.roleTitleInactive}>Resident</Text>
                    <Text style={styles.roleSubtitleInactive}>Access your account</Text>
                  </View>
                </View>
              )}
            </TouchableOpacity>

            <View style={{ width: 12 }} />

            <TouchableOpacity activeOpacity={0.8} style={{ flex: 1 }} onPress={() => setRole('admin')}>
              {role === 'admin' ? (
                <LinearGradient colors={['#6366F1', '#4F46E5']} start={{x: 0, y: 0}} end={{x: 1, y: 1}} style={styles.roleActive}>
                  <View style={styles.roleIconActiveBox}>
                    <MaterialCommunityIcons name="shield-account-outline" size={16} color="#fff" />
                  </View>
                  <View style={styles.roleTextContainer}>
                    <Text style={styles.roleTitleActive}>Admin</Text>
                    <Text style={styles.roleSubtitleActive}>Manage everything</Text>
                  </View>
                  <View style={styles.checkCircle}>
                    <Ionicons name="checkmark" size={12} color="#4F46E5" />
                  </View>
                </LinearGradient>
              ) : (
                <View style={styles.roleInactive}>
                  <View style={styles.roleIconInactiveBox}>
                    <MaterialCommunityIcons name="shield-outline" size={16} color="#1e293b" />
                  </View>
                  <View style={styles.roleTextContainer}>
                    <Text style={styles.roleTitleInactive}>Admin</Text>
                    <Text style={styles.roleSubtitleInactive}>Manage everything</Text>
                  </View>
                </View>
              )}
            </TouchableOpacity>
          </View>

          {/* Info Box */}
          <View style={styles.infoBox}>
            <Ionicons name="information-circle-outline" size={24} color="#3B82F6" style={{ marginTop: 2 }} />
            <View style={{ marginLeft: 12, flex: 1 }}>
              <Text style={styles.infoTitle}>{role === 'resident' ? 'Resident Login' : 'Admin Login'}</Text>
              <Text style={styles.infoText}>You will be taken to the {role === 'resident' ? 'Resident' : 'Admin'} login form.</Text>
            </View>
          </View>

          {/* Proceed Button */}
          <TouchableOpacity activeOpacity={0.8} onPress={handleProceed}>
            <LinearGradient colors={['#818CF8', '#4F46E5', '#3730A3']} start={{x: 0, y: 0}} end={{x: 1, y: 1}} style={styles.proceedButton}>
              <MaterialCommunityIcons name="login-variant" size={20} color="#fff" />
              <Text style={styles.proceedButtonText}>Proceed to Login</Text>
              <View style={styles.arrowCircle}>
                <Ionicons name="arrow-forward" size={18} color="#4F46E5" />
              </View>
            </LinearGradient>
          </TouchableOpacity>

          {/* Hint text */}
          <View style={styles.hintBox}>
            <View style={styles.hintIconBox}>
              <MaterialCommunityIcons name="lightbulb-outline" size={20} color="#6366F1" />
            </View>
            <View style={{ marginLeft: 12, flex: 1 }}>
              <Text style={styles.hintTitle}>Want to login as {role === 'resident' ? 'Admin' : 'Resident'}?</Text>
              <Text style={styles.hintText}>Select {role === 'resident' ? 'Admin' : 'Resident'} role above and proceed.</Text>
            </View>
          </View>
        </View>

        {/* Footer */}
        <View style={styles.footer}>
          <View style={styles.footerLinks}>
            <View style={styles.footerLinkItem}>
              <Ionicons name="lock-closed-outline" size={12} color="#475569" />
              <Text style={styles.footerLinkText}>Privacy Policy</Text>
            </View>
            <View style={styles.footerLinkItem}>
              <Ionicons name="document-text-outline" size={12} color="#475569" />
              <Text style={styles.footerLinkText}>Terms & Conditions</Text>
            </View>
            <View style={styles.footerLinkItem}>
              <Ionicons name="aperture-outline" size={12} color="#475569" />
              <Text style={styles.footerLinkText}>Cookie Policy</Text>
            </View>
            <View style={styles.footerLinkItem}>
              <Ionicons name="copy-outline" size={12} color="#475569" />
              <Text style={styles.footerLinkText}>Copyright Notice</Text>
            </View>
          </View>
          <Text style={styles.copyrightText}>© 2026 Rent Manager. All rights reserved.</Text>
        </View>

      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8fafc',
  },
  scrollContent: {
    paddingBottom: 40,
  },
  headerSection: {
    paddingTop: 80,
    paddingHorizontal: 24,
    paddingBottom: 40,
    position: 'relative',
    overflow: 'visible', // Allow blobs to bleed slightly
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
    top: 40,
    right: -40,
    width: 250,
    height: 250,
    zIndex: 0,
    elevation: 2,
    opacity: 0.9,
    overflow: 'hidden',
    borderTopLeftRadius: 100,
    borderBottomLeftRadius: 40,
  },
  houseImage: {
    width: 250,
    height: 250,
    resizeMode: 'cover',
  },
  headerContent: {
    position: 'relative',
    zIndex: 1,
  },
  logoBox: {
    width: 72,
    height: 72,
    backgroundColor: '#fff',
    borderRadius: 20,
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.1,
    shadowRadius: 12,
    elevation: 8,
    marginBottom: 24,
  },
  logoIconGradient: {
    justifyContent: 'center',
    alignItems: 'center',
    position: 'relative',
  },
  titleSmall: {
    fontSize: 22,
    fontWeight: '700',
    color: '#0f172a',
    marginBottom: 4,
  },
  titleLargeHighlight: {
    fontSize: 34,
    fontWeight: '800',
    color: '#4F46E5',
  },
  titleLarge: {
    fontSize: 34,
    fontWeight: '800',
    color: '#0f172a',
  },
  subtitle: {
    fontSize: 14,
    color: '#475569',
    lineHeight: 20,
    maxWidth: '80%',
  },
  featureBarContainer: {
    paddingHorizontal: 20,
    marginTop: -20,
    marginBottom: 30,
    zIndex: 10,
  },
  featureBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#fff',
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 100,
    shadowColor: '#4F46E5',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.08,
    shadowRadius: 16,
    elevation: 8,
  },
  featureItem: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  featureDivider: {
    width: 1,
    height: 20,
    backgroundColor: '#e2e8f0',
    marginHorizontal: 8,
  },
  featureIconBox: {
    width: 20,
    height: 20,
    borderRadius: 10,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 6,
  },
  featureText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#1e293b',
  },
  mainCard: {
    marginHorizontal: 20,
    backgroundColor: '#fff',
    borderRadius: 24,
    padding: 20,
    shadowColor: '#4F46E5',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.08,
    shadowRadius: 24,
    elevation: 10,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  headerIconBox: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#EEF2FF',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#0f172a',
    marginBottom: 2,
  },
  cardSubtitle: {
    fontSize: 12,
    color: '#64748b',
  },
  secureBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
  },
  secureText: {
    fontSize: 11,
    fontWeight: '600',
    color: '#10B981',
    marginLeft: 4,
  },
  roleContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  roleActive: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: 16,
  },
  roleIconActiveBox: {
    width: 32,
    height: 32,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.3)',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 8,
  },
  roleTextContainer: {
    flex: 1,
  },
  roleTitleActive: {
    fontSize: 13,
    fontWeight: '700',
    color: '#fff',
    marginBottom: 2,
  },
  roleSubtitleActive: {
    fontSize: 9,
    color: 'rgba(255,255,255,0.8)',
  },
  checkCircle: {
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: '#fff',
    justifyContent: 'center',
    alignItems: 'center',
  },
  roleInactive: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#f1f5f9',
    backgroundColor: '#fff',
  },
  roleIconInactiveBox: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#f8fafc',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 8,
  },
  roleTitleInactive: {
    fontSize: 13,
    fontWeight: '700',
    color: '#0f172a',
    marginBottom: 2,
  },
  roleSubtitleInactive: {
    fontSize: 9,
    color: '#64748b',
  },
  infoBox: {
    flexDirection: 'row',
    backgroundColor: '#EFF6FF',
    padding: 16,
    borderRadius: 12,
    marginTop: 24,
    marginBottom: 24,
  },
  infoTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: '#1e3a8a',
    marginBottom: 4,
  },
  infoText: {
    fontSize: 13,
    color: '#1e3a8a',
    lineHeight: 18,
  },
  proceedButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 16,
    borderRadius: 100,
    paddingLeft: 24,
    paddingRight: 8,
  },
  proceedButtonText: {
    fontSize: 16,
    fontWeight: '700',
    color: '#fff',
    flex: 1,
    marginLeft: 12,
  },
  arrowCircle: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#fff',
    justifyContent: 'center',
    alignItems: 'center',
  },
  hintBox: {
    flexDirection: 'row',
    marginTop: 24,
    alignItems: 'center',
  },
  hintIconBox: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#EEF2FF',
    justifyContent: 'center',
    alignItems: 'center',
  },
  hintTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: '#0f172a',
  },
  hintText: {
    fontSize: 12,
    color: '#64748b',
  },
  footer: {
    marginTop: 40,
    paddingHorizontal: 24,
  },
  footerLinks: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'center',
    marginBottom: 16,
    gap: 12,
  },
  footerLinkItem: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  footerLinkText: {
    fontSize: 11,
    fontWeight: '600',
    color: '#475569',
    marginLeft: 4,
  },
  copyrightText: {
    textAlign: 'center',
    fontSize: 11,
    color: '#94a3b8',
  }
});
