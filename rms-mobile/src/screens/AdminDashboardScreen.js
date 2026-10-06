import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, SafeAreaView, Dimensions, Platform, Image, ActivityIndicator, StatusBar } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import axios from 'axios';

const { width } = Dimensions.get('window');

const AdminDashboardScreen = ({ navigation }) => {
  const [stats, setStats] = useState({
    totalRevenue: 0,
    trendRevenue: 0,
    rentCollected: 0,
    trendRent: 0,
    totalDues: 0,
    trendDues: 0,
    electricityPaid: 0,
    trendElectricity: 0,
    maintenance: 0,
    residents: 0,
    currentMonthName: 'Month',
  });
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    try {
      const response = await axios.get('http://192.168.1.19/renter-system/api/admin/dashboard_stats.php');
      if (response.data.status === 'success') {
        setStats(response.data.data);
      }
    } catch (error) {
      console.error("Error fetching stats:", error);
    } finally {
      setIsLoading(false);
    }
  };

  const renderKpiCard = (title, value, subLabel, iconName, iconColor, bgColor, trend, isCurrency = true, valueColor = '#0f172a') => {
    return (
      <View style={styles.kpiCard} key={title}>
        <View style={styles.kpiCardHeader}>
          <View style={[styles.kpiIconBox, { backgroundColor: bgColor }]}>
            <Ionicons name={iconName} size={22} color={iconColor} />
          </View>
          {trend !== undefined && (
            <View style={[styles.kpiTrendBox, { backgroundColor: trend >= 0 ? '#DCFCE7' : '#FEE2E2' }]}>
              <Ionicons name={trend >= 0 ? "trending-up" : "trending-down"} size={14} color={trend >= 0 ? '#16A34A' : '#DC2626'} />
              <Text style={[styles.kpiTrendText, { color: trend >= 0 ? '#16A34A' : '#DC2626' }]}>
                {Math.abs(trend)}%
              </Text>
            </View>
          )}
        </View>
        <Text style={[styles.kpiValue, { color: valueColor }]} numberOfLines={1} adjustsFontSizeToFit>
          {isLoading ? '...' : (isCurrency ? `₹${(value || 0).toLocaleString('en-IN')}` : (value || 0))}
        </Text>
        <Text style={styles.kpiTitle}>{title}</Text>
        <Text style={[styles.kpiSubLabel, subLabel === 'Active Residents' ? { color: '#16A34A' } : (subLabel === 'Need Attention' ? { color: '#EA580C' } : {})]}>
          {subLabel}
        </Text>
      </View>
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scrollContainer} showsVerticalScrollIndicator={false}>
        
        {/* --- Header Section --- */}
        <View style={styles.header}>
          <View style={styles.userInfo}>
            <Image 
              source={{ uri: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=facearea&facepad=2&w=100&h=100&q=80' }} 
              style={styles.profilePic} 
            />
            <View>
              <Text style={styles.greeting}>Good Morning,</Text>
              <Text style={styles.userName}>Super Admin</Text>
            </View>
          </View>
          <TouchableOpacity style={styles.notificationButton} onPress={() => navigation.navigate('Notifications')}>
            <Ionicons name="notifications-outline" size={24} color="#1E293B" />
            {(stats.notificationsCount > 0) && <View style={styles.badge} />}
          </TouchableOpacity>
        </View>

        {/* --- Top Dashboard Card (Total Revenue / Main Metric) --- */}
        <LinearGradient
          colors={['#4F46E5', '#6366F1']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.mainCard}
        >
          <View style={styles.mainCardHeader}>
            <Text style={styles.mainCardTitle}>Total Revenue</Text>
            <TouchableOpacity>
              <Ionicons name="ellipsis-horizontal" size={20} color="#fff" />
            </TouchableOpacity>
          </View>
          <Text style={styles.mainCardAmount}>
            {isLoading ? <ActivityIndicator color="#fff" size="small" /> : `₹${(stats.totalRevenue || 0).toLocaleString('en-IN')}`}
          </Text>
          <Text style={styles.mainCardSubtitle}>+12.5% from last month</Text>
          
          <View style={styles.waveGraphic} />
        </LinearGradient>

        {/* --- 6-Card KPI Grid --- */}
        <View style={styles.kpiGrid}>
          {renderKpiCard("Rent Collected", stats.rentCollected, "All Time", "wallet", "#16A34A", "#DCFCE7", stats.trendRent)}
          {renderKpiCard("Total Dues", stats.totalDues, "All Residents", "alert-circle", "#DC2626", "#FEE2E2", stats.trendDues, true, "#DC2626")}
          {renderKpiCard(`Electricity Paid (${stats.currentMonthName})`, stats.electricityPaid, "This Month", "flash", "#2563EB", "#DBEAFE", stats.trendElectricity)}
          {renderKpiCard("Pending Queries", stats.maintenance, "Need Attention", "chatbubble-ellipses", "#EA580C", "#FFEDD5", undefined, false)}
          {renderKpiCard("Total Revenue", stats.totalRevenue, "All Time", "stats-chart", "#7C3AED", "#EDE9FE", stats.trendRevenue)}
          {renderKpiCard("Total Residents", stats.residents, "Active Residents", "people", "#16A34A", "#DCFCE7", undefined, false)}
        </View>

        {/* --- Quick Actions --- */}
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Quick Actions</Text>
        </View>
        <View style={styles.actionsRow}>
          <TouchableOpacity style={styles.actionItem} onPress={() => navigation.navigate('Billing')}>
            <View style={styles.actionIconWrapper}>
              <Ionicons name="receipt" size={24} color="#6366F1" />
            </View>
            <Text style={styles.actionText}>Generate Bill</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.actionItem} onPress={() => navigation.navigate('Residents')}>
            <View style={styles.actionIconWrapper}>
              <Ionicons name="people" size={24} color="#10B981" />
            </View>
            <Text style={styles.actionText}>Directory</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.actionItem}>
            <View style={styles.actionIconWrapper}>
              <Ionicons name="document-text" size={24} color="#F59E0B" />
            </View>
            <Text style={styles.actionText}>Report</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.actionItem}>
            <View style={styles.actionIconWrapper}>
              <Ionicons name="log-in" size={24} color="#EF4444" />
            </View>
            <Text style={styles.actionText}>Visitor</Text>
          </TouchableOpacity>
        </View>

        {/* --- Recent Electricity Records --- */}
        <View style={styles.modernCard}>
          <View style={styles.cardHeader}>
            <Text style={styles.cardTitle}>Recent Electricity Records</Text>
            <TouchableOpacity style={styles.viewAllBtn} onPress={() => navigation.navigate('ElectricityRecords')}>
              <Text style={styles.viewAllBtnText}>View All</Text>
            </TouchableOpacity>
          </View>

          {stats.recentElectricity && stats.recentElectricity.map((item, index) => (
            <View key={index} style={styles.recordItem}>
              <View style={styles.recordAvatarWrapper}>
                <View style={[styles.recordAvatar, { backgroundColor: ['#E0F2FE', '#D1FAE5', '#FCE7F3', '#E0E7FF'][index % 4] }]}>
                  {item.profile_pic ? (
                    <Image source={{ uri: `http://192.168.1.19/renter-system/${item.profile_pic}` }} style={styles.avatarImg} />
                  ) : (
                    <Text style={styles.avatarInitial}>{item.name.charAt(0)}</Text>
                  )}
                </View>
              </View>
              <View style={styles.recordInfo}>
                <Text style={styles.recordName}>{item.name}</Text>
                <Text style={styles.recordMeta}>{item.month}  •  {item.units} Units</Text>
              </View>
              <View style={[styles.statusBadge, item.status === 'Paid' ? styles.badgePaid : styles.badgeDue]}>
                <Text style={[styles.statusBadgeText, item.status === 'Paid' ? styles.badgeTextPaid : styles.badgeTextDue]}>
                  {item.status}
                </Text>
              </View>
              <Text style={styles.recordAmount}>₹{item.amount.toLocaleString('en-IN')}</Text>
            </View>
          ))}

          <TouchableOpacity style={styles.cardFooterBtn} onPress={() => navigation.navigate('ElectricityRecords')}>
            <Text style={styles.cardFooterBtnText}>View All Records</Text>
            <Ionicons name="chevron-forward" size={16} color="#6366F1" />
          </TouchableOpacity>
        </View>

        {/* --- Pending Payments --- */}
        <View style={[styles.modernCard, { marginTop: 20 }]}>
          <View style={styles.cardHeader}>
            <Text style={styles.cardTitle}>Pending Payments</Text>
            <View style={styles.countBadge}>
              <Text style={styles.countBadgeText}>{stats.totalPendingCount || 0}</Text>
            </View>
          </View>

          {stats.pendingPayments && stats.pendingPayments.map((item, index) => (
            <View key={index} style={styles.pendingItem}>
              <View style={styles.pendingInfo}>
                <Text style={styles.pendingName}>{item.name}</Text>
                <Text style={styles.pendingRoom}>Room {item.room_no}</Text>
              </View>
              <Text style={styles.pendingAmount}>₹{item.amount.toLocaleString('en-IN')}</Text>
              <View style={styles.pendingStatusBadge}>
                <Ionicons name="pin" size={10} color="#EF4444" style={{marginRight: 4}} />
                <Text style={styles.pendingStatusText}>Due</Text>
              </View>
            </View>
          ))}

          <TouchableOpacity style={styles.cardFooterBtnLeft} onPress={() => navigation.navigate('PendingPayments')}>
            <Text style={styles.cardFooterBtnText}>View All</Text>
            <Ionicons name="chevron-forward" size={16} color="#6366F1" />
          </TouchableOpacity>
        </View>
        
        {/* Bottom Spacing */}
        <View style={{ height: 100 }} />
      </ScrollView>

      {/* --- Floating Bottom Navigation --- */}
      <View style={styles.bottomNavContainer}>
        <View style={styles.bottomNav}>
          <TouchableOpacity style={styles.navItem}>
            <Ionicons name="home" size={24} color="#4F46E5" />
            <Text style={[styles.navText, { color: '#4F46E5', fontWeight: '600' }]}>Home</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.navItem} onPress={() => navigation.navigate('Residents')}>
            <Ionicons name="people-outline" size={24} color="#94A3B8" />
            <Text style={styles.navText}>Residents</Text>
          </TouchableOpacity>
          <View style={styles.navItemCenter}>
            <TouchableOpacity style={styles.centerButton}>
              <Ionicons name="add" size={32} color="#fff" />
            </TouchableOpacity>
          </View>
          <TouchableOpacity style={styles.navItem} onPress={() => navigation.navigate('Billing')}>
            <Ionicons name="wallet-outline" size={24} color="#94A3B8" />
            <Text style={styles.navText}>Billing</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.navItem} onPress={() => navigation.navigate('Profile')}>
            <Ionicons name="person-outline" size={24} color="#94A3B8" />
            <Text style={styles.navText}>Profile</Text>
          </TouchableOpacity>
        </View>
      </View>

    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  scrollContainer: {
    padding: 24,
    paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight + 20 : 30,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 32,
  },
  userInfo: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  profilePic: {
    width: 48,
    height: 48,
    borderRadius: 24,
    marginRight: 16,
    borderWidth: 2,
    borderColor: '#fff',
  },
  greeting: {
    fontSize: 14,
    color: '#64748B',
    marginBottom: 2,
  },
  userName: {
    fontSize: 18,
    fontWeight: '700',
    color: '#0F172A',
  },
  notificationButton: {
    width: 48,
    height: 48,
    backgroundColor: '#fff',
    borderRadius: 24,
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 3,
  },
  badge: {
    position: 'absolute',
    top: 12,
    right: 14,
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#EF4444',
    borderWidth: 1,
    borderColor: '#fff',
  },
  mainCard: {
    borderRadius: 24,
    padding: 24,
    marginBottom: 24,
    overflow: 'hidden',
    shadowColor: '#4F46E5',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.2,
    shadowRadius: 16,
    elevation: 10,
  },
  mainCardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  mainCardTitle: {
    fontSize: 14,
    color: '#E0E7FF',
    fontWeight: '500',
  },
  mainCardAmount: {
    fontSize: 36,
    fontWeight: '800',
    color: '#fff',
    marginBottom: 8,
  },
  mainCardSubtitle: {
    fontSize: 14,
    color: '#A5B4FC',
  },
  waveGraphic: {
    position: 'absolute',
    bottom: -40,
    right: -40,
    width: 150,
    height: 150,
    borderRadius: 75,
    backgroundColor: 'rgba(255,255,255,0.1)',
  },
  kpiGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    marginBottom: 32,
  },
  kpiCard: {
    width: '48%',
    backgroundColor: '#fff',
    borderRadius: 20,
    padding: 16,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.03,
    shadowRadius: 8,
    elevation: 2,
  },
  kpiCardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 16,
  },
  kpiIconBox: {
    width: 44,
    height: 44,
    borderRadius: 16,
    justifyContent: 'center',
    alignItems: 'center',
  },
  kpiTrendBox: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 6,
    paddingVertical: 4,
    borderRadius: 12,
  },
  kpiTrendText: {
    fontSize: 11,
    fontWeight: '700',
    marginLeft: 2,
  },
  kpiValue: {
    fontSize: 22,
    fontWeight: '800',
    marginBottom: 6,
  },
  kpiTitle: {
    fontSize: 12,
    fontWeight: '600',
    color: '#0F172A',
    marginBottom: 2,
  },
  kpiSubLabel: {
    fontSize: 11,
    color: '#64748B',
    fontWeight: '500',
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: '#0F172A',
  },
  seeAllText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#4F46E5',
  },
  actionsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 32,
  },
  actionItem: {
    alignItems: 'center',
    width: '23%',
  },
  actionIconWrapper: {
    width: 60,
    height: 60,
    borderRadius: 20,
    backgroundColor: '#fff',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.04,
    shadowRadius: 10,
    elevation: 2,
  },
  actionText: {
    fontSize: 12,
    color: '#475569',
    fontWeight: '500',
  },
  activityList: {
    backgroundColor: '#fff',
    borderRadius: 24,
    padding: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.03,
    shadowRadius: 8,
    elevation: 2,
  },
  activityItem: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 20,
  },
  activityIconBox: {
    width: 44,
    height: 44,
    borderRadius: 14,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 16,
  },
  activityContent: {
    flex: 1,
  },
  activityTitle: {
    fontSize: 15,
    fontWeight: '600',
    color: '#0F172A',
    marginBottom: 4,
  },
  activityTime: {
    fontSize: 13,
    color: '#64748B',
  },
  modernCard: {
    backgroundColor: '#fff', borderRadius: 24, padding: 20,
    shadowColor: '#4F46E5', shadowOffset: { width: 0, height: 8 }, shadowOpacity: 0.05, shadowRadius: 20, elevation: 5,
  },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 },
  cardTitle: { fontSize: 16, fontWeight: '800', color: '#0F172A' },
  viewAllBtn: { backgroundColor: '#EEF2FF', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 12 },
  viewAllBtnText: { color: '#4F46E5', fontSize: 12, fontWeight: '700' },
  countBadge: { backgroundColor: '#EEF2FF', width: 28, height: 28, borderRadius: 14, justifyContent: 'center', alignItems: 'center' },
  countBadgeText: { color: '#4F46E5', fontSize: 13, fontWeight: '800' },

  recordItem: { flexDirection: 'row', alignItems: 'center', marginBottom: 20 },
  recordAvatarWrapper: { marginRight: 12 },
  recordAvatar: { width: 44, height: 44, borderRadius: 22, justifyContent: 'center', alignItems: 'center', overflow: 'hidden' },
  avatarImg: { width: 44, height: 44 },
  avatarInitial: { fontSize: 18, fontWeight: '700', color: '#0F172A' },
  recordInfo: { flex: 1 },
  recordName: { fontSize: 15, fontWeight: '700', color: '#0F172A', marginBottom: 4 },
  recordMeta: { fontSize: 12, color: '#64748B', fontWeight: '500' },
  statusBadge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 10, marginRight: 12 },
  badgePaid: { backgroundColor: '#D1FAE5' },
  badgeDue: { backgroundColor: '#FEE2E2' },
  statusBadgeText: { fontSize: 11, fontWeight: '700' },
  badgeTextPaid: { color: '#059669' },
  badgeTextDue: { color: '#DC2626' },
  recordAmount: { fontSize: 16, fontWeight: '800', color: '#0F172A' },

  cardFooterBtn: { flexDirection: 'row', justifyContent: 'center', alignItems: 'center', marginTop: 4 },
  cardFooterBtnLeft: { flexDirection: 'row', justifyContent: 'center', alignItems: 'center', marginTop: 10 },
  cardFooterBtnText: { color: '#4F46E5', fontSize: 14, fontWeight: '700', marginRight: 4 },

  pendingItem: { flexDirection: 'row', alignItems: 'center', marginBottom: 20 },
  pendingInfo: { flex: 1 },
  pendingName: { fontSize: 15, fontWeight: '700', color: '#4F46E5', marginBottom: 4 },
  pendingRoom: { fontSize: 13, color: '#64748B', fontWeight: '500' },
  pendingAmount: { fontSize: 15, fontWeight: '800', color: '#0F172A', marginRight: 16 },
  pendingStatusBadge: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FEE2E2', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 12 },
  pendingStatusText: { color: '#DC2626', fontSize: 12, fontWeight: '700' },

  bottomNavContainer: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    paddingHorizontal: 24,
    paddingBottom: Platform.OS === 'ios' ? 34 : 24,
    backgroundColor: 'transparent',
  },
  bottomNav: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 32,
    paddingVertical: 16,
    paddingHorizontal: 24,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.08,
    shadowRadius: 20,
    elevation: 15,
  },
  navItem: {
    alignItems: 'center',
    justifyContent: 'center',
    width: 50,
  },
  navText: {
    fontSize: 10,
    marginTop: 4,
    color: '#94A3B8',
    fontWeight: '500',
  },
  navItemCenter: {
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: -40,
  },
  centerButton: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: '#4F46E5',
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#4F46E5',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.3,
    shadowRadius: 16,
    elevation: 10,
    borderWidth: 4,
    borderColor: '#F8FAFC',
  }
});

export default AdminDashboardScreen;
