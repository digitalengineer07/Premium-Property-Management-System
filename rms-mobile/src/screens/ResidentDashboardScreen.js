import React, { useState, useCallback } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, RefreshControl, SafeAreaView, Dimensions, Image, ImageBackground, ActivityIndicator, Alert, StatusBar, Modal, FlatList } from 'react-native';
import { Ionicons, MaterialCommunityIcons, Feather } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { useFocusEffect } from '@react-navigation/native';
import axios from 'axios';

const { width, height } = Dimensions.get('window');
const API_URL = 'http://192.168.1.19/renter-system/api/resident/dashboard_stats.php';

// Mock Header Image (Using the AI generated one)
// Note: Normally we'd use require(), but we'll use an absolute path for local expo dev
const HEADER_BG_IMAGE = 'http://192.168.1.19/renter-system/assets/img/resident_header_bg.png'; // We will just use the generated image URI if available, or a gradient fallback. 

export default function ResidentDashboardScreen({ route, navigation }) {
  const { token } = route.params || {};

  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [data, setData] = useState({
    profile: {},
    kpis: { rent_due: 0, electricity_due: 0, maintenance_due: 0, advance_deposit: 0, total_due: 0, carry_forward: 0 },
    recent_activity: [],
    reminders: []
  });

  const [isMonthPickerVisible, setIsMonthPickerVisible] = useState(false);
  const [selectedMonth, setSelectedMonth] = useState(() => {
    const d = new Date();
    d.setMonth(d.getMonth() - 1);
    return d.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
  });

  const generatePastMonths = () => {
    const months = [];
    const d = new Date();
    for (let i = 0; i < 12; i++) {
      const past = new Date(d.getFullYear(), d.getMonth() - i, 1);
      months.push(past.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' }));
    }
    return months;
  };

  const fetchDashboardData = async () => {
    try {
      const response = await axios.get(`${API_URL}?token=${encodeURIComponent(token)}&month=${encodeURIComponent(selectedMonth)}`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Cache-Control': 'no-cache' }
      });
      if (response.data.status === 'success') {
        setData(response.data.data);
      } else {
        Alert.alert('Error', response.data.message);
      }
    } catch (error) {
      if (error.response && error.response.status === 401) {
        Alert.alert('Session Expired', 'Please login again.');
        navigation.replace('Login');
      } else {
        Alert.alert('Network Error', 'Failed to load dashboard.');
      }
    } finally {
      setIsLoading(false);
      setRefreshing(false);
    }
  };

  useFocusEffect(
    useCallback(() => {
      if (token) fetchDashboardData();
      else navigation.replace('Login');
    }, [token, selectedMonth])
  );

  const onRefresh = () => {
    setRefreshing(true);
    fetchDashboardData();
  };

  const { profile, kpis, recent_activity, reminders } = data;

  if (isLoading && !refreshing) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#6366F1" />
        <Text style={{ marginTop: 12, color: '#64748b' }}>Loading dashboard...</Text>
      </View>
    );
  }

  // Calculate generic "Paid Amount" for UI since it's in the design
  const paidAmount = kpis.advance_deposit || 0; // Mock mapping for this specific UI card

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
          >
            <ImageBackground 
              source={{ uri: 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&q=80&w=800' }} 
              style={styles.headerImageBg}
              imageStyle={{ opacity: 0.15 }}
            >
              <SafeAreaView style={{ flex: 1 }}>
                <View style={styles.topNav}>
                  <View style={styles.userInfoRow}>
                    <Image 
                      source={{ uri: profile.profile_pic ? `http://192.168.1.19/renter-system/${profile.profile_pic}` : 'https://images.unsplash.com/photo-1599566150163-29194dcaad36?auto=format&fit=facearea&facepad=2&w=100&h=100&q=80' }} 
                      style={styles.profilePic} 
                    />
                    <View style={styles.userNameContainer}>
                      <Text style={styles.greetingText}>Hello, 👋</Text>
                      <Text style={styles.userNameText}>{profile.name || 'Resident'}</Text>
                      <View style={styles.roomBadge}>
                        <Ionicons name="home-outline" size={12} color="#5E4BF5" />
                        <Text style={styles.roomBadgeText}>Room {profile.room_no}</Text>
                      </View>
                    </View>
                  </View>

                  <TouchableOpacity style={styles.notificationBtn} onPress={() => navigation.navigate('Notifications')}>
                    <Ionicons name="notifications" size={24} color="#FFFFFF" />
                    {reminders.length > 0 && <View style={styles.notificationDot} ><Text style={styles.notificationDotText}>{reminders.length}</Text></View>}
                  </TouchableOpacity>
                </View>
              </SafeAreaView>
            </ImageBackground>
          </LinearGradient>
          
          {/* Wave Overlay Fake via Circle */}
          <View style={styles.waveBase} />
          <View style={styles.waveOverlay} />
        </View>

        {/* OUTSTANDING CARD (Overlaps Header) */}
        <View style={styles.outstandingCardWrapper}>
          <View style={styles.outstandingCard}>
            <View style={styles.outstandingCardLeft}>
              <Text style={styles.outstandingLabel}>Total Outstanding</Text>
              <Text style={styles.outstandingAmount}>₹ {kpis.total_due > 0 ? kpis.total_due.toLocaleString('en-IN') : '0'}</Text>
              <Text style={styles.outstandingDate}>Due as on {new Date().toLocaleDateString('en-GB', {day: 'numeric', month: 'short', year: 'numeric'})}</Text>
            </View>
            <TouchableOpacity style={styles.viewBillBtn} onPress={() => navigation.navigate('ResidentBills', { token })}>
              <Ionicons name="document-text-outline" size={16} color="#FFFFFF" />
              <Text style={styles.viewBillText}>View Bill</Text>
              <Ionicons name="chevron-forward" size={16} color="#FFFFFF" />
            </TouchableOpacity>
          </View>
        </View>

        {/* OVERVIEW SECTION */}
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Your Overview</Text>
          <TouchableOpacity style={styles.monthSelector} onPress={() => setIsMonthPickerVisible(true)}>
            <Text style={styles.monthSelectorText}>{selectedMonth}</Text>
            <Ionicons name="calendar-outline" size={16} color="#64748B" />
          </TouchableOpacity>
        </View>

        <View style={styles.overviewGrid}>
          <OverviewCard icon="home" color="#EF4444" bg="#FEF2F2" label="Rent Due" amount={kpis.rent_due} />
          <OverviewCard icon="flash" color="#F59E0B" bg="#FEF9C3" label="Electricity Due" amount={kpis.electricity_due} />
          <OverviewCard icon="build" color="#8B5CF6" bg="#F3E8FF" label="Maintenance Due" amount={kpis.maintenance_due} />
          <OverviewCard icon="wallet" color="#10B981" bg="#DCFCE7" label="Advance / Deposit" amount={kpis.advance_deposit} />
          <OverviewCard icon="cash" color="#3B82F6" bg="#EFF6FF" label="Paid Amount" amount={paidAmount} />
          <OverviewCard icon="document" color="#64748B" bg="#F8FAFC" label="Carry Forward" amount={kpis.carry_forward} />
        </View>

        {/* QUICK ACTIONS */}
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Quick Actions</Text>
        </View>

        <View style={styles.quickActionsRow}>
          <QuickActionBtn icon="card" color="#8B5CF6" bg="#F3E8FF" label="Pay Dues" onPress={() => navigation.navigate('ResidentPay', { token })} />
          <QuickActionBtn icon="chatbubble-ellipses" color="#10B981" bg="#DCFCE7" label="Raise Query" onPress={() => navigation.navigate('ResidentQueries', { token })} />
          <QuickActionBtn icon="document-text" color="#3B82F6" bg="#EFF6FF" label="View Bill Slip" onPress={() => navigation.navigate('ResidentBills', { token })} />
          <QuickActionBtn icon="receipt" color="#F59E0B" bg="#FEF9C3" label="Payment History" onPress={() => navigation.navigate('ResidentDocuments', { token })} />
        </View>

        {/* REMINDER BANNER */}
        {kpis.total_due > 0 && (
          <View style={styles.reminderBanner}>
            <View style={styles.reminderIconBg}>
              <Ionicons name="notifications" size={24} color="#EF4444" />
            </View>
            <View style={styles.reminderContent}>
              <Text style={styles.reminderTitle}>You have pending dues of <Text style={{color: '#EF4444'}}>₹ {kpis.total_due.toLocaleString('en-IN')}</Text></Text>
              <Text style={styles.reminderSub}>Please clear your dues to avoid late fee.</Text>
            </View>
            <TouchableOpacity style={styles.reminderBtn} onPress={() => navigation.navigate('ResidentPay', { token })}>
              <Text style={styles.reminderBtnText}>Pay Now</Text>
              <Ionicons name="chevron-forward" size={14} color="#EF4444" />
            </TouchableOpacity>
          </View>
        )}

        {/* RECENT TRANSACTIONS */}
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Recent Transactions</Text>
          <TouchableOpacity><Text style={styles.viewAllText}>View All</Text></TouchableOpacity>
        </View>

        <View style={styles.transactionsList}>
          {recent_activity.length === 0 ? (
            <Text style={{textAlign: 'center', color: '#94A3B8', marginVertical: 20}}>No recent transactions.</Text>
          ) : (
            recent_activity.slice(0, 4).map((item, index) => (
              <TransactionItem key={index} item={item} />
            ))
          )}
        </View>

      </ScrollView>

      {/* CUSTOM BOTTOM NAVIGATION BAR */}
      <View style={styles.bottomTabBar}>
        <BottomTabBtn icon="home" label="Home" active />
        <BottomTabBtn icon="document-text-outline" label="Bills" onPress={() => navigation.navigate('ResidentBills', { token })} />
        <View style={{ width: 60 }} />
        <BottomTabBtn icon="chatbubbles-outline" label="Queries" onPress={() => navigation.navigate('ResidentQueries', { token })} />
        <BottomTabBtn icon="person-outline" label="Profile" onPress={() => navigation.navigate('ResidentProfile', { token })} />

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

      {/* MONTH PICKER MODAL */}
      <Modal visible={isMonthPickerVisible} transparent animationType="fade">
        <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={() => setIsMonthPickerVisible(false)}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Select Month</Text>
              <TouchableOpacity onPress={() => setIsMonthPickerVisible(false)}>
                <Ionicons name="close" size={24} color="#64748B" />
              </TouchableOpacity>
            </View>
            <FlatList
              data={generatePastMonths()}
              keyExtractor={(item) => item}
              showsVerticalScrollIndicator={false}
              renderItem={({ item }) => (
                <TouchableOpacity 
                  style={[styles.monthOptionBtn, selectedMonth === item && styles.monthOptionBtnActive]}
                  onPress={() => {
                    setSelectedMonth(item);
                    setIsMonthPickerVisible(false);
                  }}
                >
                  <Text style={[styles.monthOptionText, selectedMonth === item && styles.monthOptionTextActive]}>{item}</Text>
                  {selectedMonth === item && <Ionicons name="checkmark-circle" size={20} color="#5E4BF5" />}
                </TouchableOpacity>
              )}
            />
          </View>
        </TouchableOpacity>
      </Modal>
    </View>
  );
}

// Components
const OverviewCard = ({ icon, color, bg, label, amount }) => (
  <View style={[styles.overviewCard, { borderColor: bg }]}>
    <View style={[styles.overviewIcon, { backgroundColor: bg }]}>
      <Ionicons name={icon} size={20} color={color} />
    </View>
    <Text style={styles.overviewLabel}>{label}</Text>
    <Text style={[styles.overviewAmount, { color: color }]}>₹ {(amount || 0).toLocaleString('en-IN')}</Text>
  </View>
);

const QuickActionBtn = ({ icon, color, bg, label, onPress }) => (
  <TouchableOpacity style={styles.quickActionBtn} onPress={onPress}>
    <View style={styles.quickActionBox}>
      <View style={[styles.quickActionIconBg, { backgroundColor: color }]}>
        <Ionicons name={icon} size={22} color="#FFFFFF" />
      </View>
      <Text style={styles.quickActionLabel}>{label}</Text>
    </View>
  </TouchableOpacity>
);

const TransactionItem = ({ item }) => {
  let icon = 'document-text';
  let color = '#10B981';
  let bg = '#DCFCE7';
  
  if (item.type?.toLowerCase() === 'electricity') { icon = 'flash'; color = '#F59E0B'; bg = '#FEF9C3'; }
  else if (item.type?.toLowerCase() === 'maintenance') { icon = 'build'; color = '#8B5CF6'; bg = '#F3E8FF'; }

  const isPartial = item.status?.toLowerCase() === 'partial';

  return (
    <View style={styles.transactionItem}>
      <View style={[styles.txIconBg, { backgroundColor: bg }]}>
        <Ionicons name={icon} size={20} color={color} />
      </View>
      <View style={styles.txDetails}>
        <Text style={styles.txTitle}>{item.type} Payment {isPartial ? '(Partial)' : ''}</Text>
        <Text style={styles.txSub}>{item.date} • {item.mode || 'Online'}</Text>
      </View>
      <View style={styles.txAmountContainer}>
        <Text style={styles.txAmount}>₹ {(item.amount || 0).toLocaleString('en-IN')}</Text>
        <View style={[styles.txBadge, { backgroundColor: isPartial ? '#FEF9C3' : '#DCFCE7' }]}>
          <Text style={[styles.txBadgeText, { color: isPartial ? '#D97706' : '#059669' }]}>
            {isPartial ? 'Partial' : 'Paid'}
          </Text>
        </View>
      </View>
    </View>
  );
};

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
    height: 350,
    width: '100%',
    position: 'relative',
    backgroundColor: '#FCFBFC',
    overflow: 'hidden'
  },
  headerGradient: {
    height: 350,
    width: '100%',
    position: 'absolute',
    top: 0,
  },
  headerImageBg: {
    width: '100%',
    height: '100%',
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
    paddingHorizontal: 24,
    paddingTop: 50,
    alignItems: 'flex-start'
  },
  userInfoRow: { flexDirection: 'row', alignItems: 'center' },
  profilePic: { width: 70, height: 70, borderRadius: 35, borderWidth: 3, borderColor: '#FFFFFF', marginRight: 16 },
  userNameContainer: { justifyContent: 'center' },
  greetingText: { color: 'rgba(255,255,255,0.9)', fontSize: 16, fontWeight: '500', marginBottom: 2 },
  userNameText: { color: '#FFFFFF', fontSize: 26, fontWeight: '700', marginBottom: 6 },
  roomBadge: { backgroundColor: '#FFFFFF', flexDirection: 'row', alignItems: 'center', paddingHorizontal: 12, paddingVertical: 4, borderRadius: 16, alignSelf: 'flex-start' },
  roomBadgeText: { color: '#5E4BF5', fontSize: 12, fontWeight: '700', marginLeft: 6 },
  
  notificationBtn: { width: 44, height: 44, borderRadius: 14, borderWidth: 1, borderColor: 'rgba(255,255,255,0.5)', justifyContent: 'center', alignItems: 'center' },
  notificationDot: { position: 'absolute', top: -6, right: -6, backgroundColor: '#EF4444', borderRadius: 10, minWidth: 20, height: 20, justifyContent: 'center', alignItems: 'center', borderWidth: 2, borderColor: '#5b42f3' },
  notificationDotText: { color: '#FFFFFF', fontSize: 10, fontWeight: 'bold' },

  outstandingCardWrapper: {
    paddingHorizontal: 24,
    marginTop: -140, // Pulls it over the header curve
    marginBottom: 24,
    zIndex: 10,
  },
  outstandingCard: {
    backgroundColor: '#1E1B4B', // Deep navy
    borderRadius: 20,
    padding: 24,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    shadowColor: '#1E1B4B', shadowOffset: { width: 0, height: 10 }, shadowOpacity: 0.4, shadowRadius: 15, elevation: 12,
  },
  outstandingCardLeft: { flex: 1 },
  outstandingLabel: { color: 'rgba(255,255,255,0.8)', fontSize: 14, fontWeight: '500', marginBottom: 6 },
  outstandingAmount: { color: '#EF4444', fontSize: 34, fontWeight: '800', marginBottom: 6 },
  outstandingDate: { color: 'rgba(255,255,255,0.6)', fontSize: 12 },
  viewBillBtn: { flexDirection: 'row', alignItems: 'center', backgroundColor: 'rgba(255,255,255,0.15)', paddingHorizontal: 16, paddingVertical: 12, borderRadius: 20 },
  viewBillText: { color: '#FFFFFF', fontSize: 13, fontWeight: '600', marginHorizontal: 6 },

  sectionHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 24, marginBottom: 16 },
  sectionTitle: { fontSize: 18, fontWeight: '700', color: '#0F172A' },
  monthSelector: { flexDirection: 'row', alignItems: 'center' },
  monthSelectorText: { fontSize: 14, color: '#64748B', marginRight: 4, fontWeight: '500' },
  viewAllText: { fontSize: 14, color: '#5b42f3', fontWeight: '600' },

  overviewGrid: { flexDirection: 'row', flexWrap: 'wrap', paddingHorizontal: 16, justifyContent: 'center' },
  overviewCard: { width: '44%', backgroundColor: '#FFFFFF', borderRadius: 16, padding: 16, margin: 8, shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.05, shadowRadius: 8, elevation: 2, borderWidth: 1 },
  overviewIcon: { width: 36, height: 36, borderRadius: 18, justifyContent: 'center', alignItems: 'center', marginBottom: 12 },
  overviewLabel: { fontSize: 12, color: '#64748B', fontWeight: '500', marginBottom: 4 },
  overviewAmount: { fontSize: 20, fontWeight: '700' },

  quickActionsRow: { flexDirection: 'row', justifyContent: 'space-between', paddingHorizontal: 24, marginBottom: 30 },
  quickActionBtn: { width: '23%', alignItems: 'center' },
  quickActionBox: { backgroundColor: '#FFFFFF', padding: 12, borderRadius: 16, alignItems: 'center', shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.05, shadowRadius: 6, elevation: 2, width: '100%', height: 100 },
  quickActionIconBg: { width: 44, height: 44, borderRadius: 22, justifyContent: 'center', alignItems: 'center', marginBottom: 8 },
  quickActionLabel: { fontSize: 11, color: '#475569', fontWeight: '600', textAlign: 'center' },

  reminderBanner: { flexDirection: 'row', backgroundColor: '#FEF2F2', marginHorizontal: 24, borderRadius: 16, padding: 16, alignItems: 'center', marginBottom: 30 },
  reminderIconBg: { width: 40, height: 40, borderRadius: 20, backgroundColor: '#FEE2E2', justifyContent: 'center', alignItems: 'center', marginRight: 12 },
  reminderContent: { flex: 1 },
  reminderTitle: { fontSize: 14, fontWeight: '700', color: '#1E293B', marginBottom: 2 },
  reminderSub: { fontSize: 12, color: '#64748B' },
  reminderBtn: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 10, paddingVertical: 6, borderWidth: 1, borderColor: '#FECACA', borderRadius: 12, backgroundColor: '#FFF' },
  reminderBtnText: { color: '#EF4444', fontSize: 12, fontWeight: '700', marginRight: 2 },

  transactionsList: { paddingHorizontal: 24 },
  transactionItem: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FFFFFF', padding: 16, borderRadius: 16, marginBottom: 12, shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.03, shadowRadius: 4, elevation: 1 },
  txIconBg: { width: 44, height: 44, borderRadius: 22, justifyContent: 'center', alignItems: 'center', marginRight: 16 },
  txDetails: { flex: 1 },
  txTitle: { fontSize: 14, fontWeight: '600', color: '#1E293B', marginBottom: 4 },
  txSub: { fontSize: 12, color: '#94A3B8' },
  txAmountContainer: { alignItems: 'flex-end' },
  txAmount: { fontSize: 15, fontWeight: '700', color: '#10B981', marginBottom: 4 },
  txBadge: { paddingHorizontal: 8, paddingVertical: 4, borderRadius: 6 },
  txBadgeText: { fontSize: 10, fontWeight: '700', textTransform: 'uppercase' },

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
  fabLabel: { fontSize: 11, fontWeight: '700', color: '#5b42f3' },

  // Month Picker Modal Styles
  modalOverlay: { flex: 1, backgroundColor: 'rgba(15, 23, 42, 0.6)', justifyContent: 'center', paddingHorizontal: 24 },
  modalContent: { backgroundColor: '#FFFFFF', borderRadius: 24, padding: 20, maxHeight: '60%' },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 },
  modalTitle: { fontSize: 18, fontWeight: '700', color: '#1E293B' },
  monthOptionBtn: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  monthOptionBtnActive: { backgroundColor: '#F3E8FF', borderRadius: 12, paddingHorizontal: 12, borderBottomWidth: 0, marginTop: 4 },
  monthOptionText: { fontSize: 16, color: '#475569', fontWeight: '500' },
  monthOptionTextActive: { color: '#5E4BF5', fontWeight: '700' }
});
