import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, StyleSheet, FlatList, ActivityIndicator, TouchableOpacity, SafeAreaView, Platform, StatusBar } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from '@react-navigation/native';
import axios from 'axios';

export default function NotificationsScreen({ navigation }) {
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);

  useFocusEffect(
    useCallback(() => {
      fetchNotifications();
    }, [])
  );

  const fetchNotifications = async () => {
    setLoading(true);
    try {
      const response = await axios.get('http://192.168.1.19/renter-system/api/admin/get_notifications.php');
      if (response.data.status === 'success') {
        setNotifications(response.data.data);
      }
    } catch (error) {
      console.error("Error fetching notifications:", error);
    } finally {
      setLoading(false);
    }
  };

  const renderItem = ({ item }) => {
    // Determine background color based on icon color for a soft tint effect
    let bgTint = '#F1F5F9';
    if (item.color === '#10B981') bgTint = '#D1FAE5'; // emerald tint
    if (item.color === '#EF4444') bgTint = '#FEE2E2'; // red tint

    return (
      <View style={styles.notificationItem}>
        <View style={[styles.iconBox, { backgroundColor: bgTint }]}>
          <Ionicons name={item.icon} size={24} color={item.color} />
        </View>
        <View style={styles.notificationContent}>
          <Text style={styles.notificationTitle}>{item.title}</Text>
          <Text style={styles.notificationMessage}>{item.message}</Text>
          <Text style={styles.notificationTime}>
            {new Date(item.date).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true })}
          </Text>
        </View>
      </View>
    );
  };

  const renderEmpty = () => (
    <View style={styles.emptyContainer}>
      <View style={styles.emptyIconBox}>
        <Ionicons name="notifications-off-outline" size={48} color="#94A3B8" />
      </View>
      <Text style={styles.emptyTitle}>You're all caught up!</Text>
      <Text style={styles.emptyText}>There are no pending queries or payment verifications requiring your attention right now.</Text>
      <TouchableOpacity style={styles.refreshBtnEmpty} onPress={fetchNotifications}>
        <Ionicons name="sync" size={18} color="#4F46E5" style={{marginRight: 6}} />
        <Text style={styles.refreshBtnTextEmpty}>Refresh List</Text>
      </TouchableOpacity>
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Ionicons name="arrow-back" size={24} color="#0F172A" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Notifications</Text>
        <TouchableOpacity style={styles.backBtn} onPress={fetchNotifications}>
          <Ionicons name="sync" size={22} color="#0F172A" />
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.loaderCenter}>
          <ActivityIndicator size="large" color="#4F46E5" />
        </View>
      ) : (
        <FlatList
          data={notifications}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          ListEmptyComponent={renderEmpty}
          contentContainerStyle={notifications.length === 0 ? styles.listContainerEmpty : styles.listContainer}
          showsVerticalScrollIndicator={false}
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
    paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 16,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  backBtn: {
    width: 40, height: 40, borderRadius: 20,
    justifyContent: 'center', alignItems: 'center'
  },
  headerTitle: {
    fontSize: 18, fontWeight: '700', color: '#0F172A'
  },
  loaderCenter: {
    flex: 1, justifyContent: 'center', alignItems: 'center'
  },
  listContainer: {
    padding: 20,
  },
  listContainerEmpty: {
    flex: 1,
    justifyContent: 'center',
    padding: 20,
  },
  notificationItem: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    padding: 20,
    borderRadius: 20,
    marginBottom: 16,
    shadowColor: '#4F46E5', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.04, shadowRadius: 12, elevation: 2
  },
  iconBox: {
    width: 48, height: 48, borderRadius: 24,
    justifyContent: 'center', alignItems: 'center',
    marginRight: 16
  },
  notificationContent: { flex: 1, justifyContent: 'center' },
  notificationTitle: { fontSize: 16, fontWeight: '700', color: '#0F172A', marginBottom: 4 },
  notificationMessage: { fontSize: 13, color: '#475569', lineHeight: 20, marginBottom: 8 },
  notificationTime: { fontSize: 11, color: '#94A3B8', fontWeight: '500' },
  
  emptyContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    padding: 20
  },
  emptyIconBox: {
    width: 100, height: 100, borderRadius: 50, backgroundColor: '#F1F5F9',
    justifyContent: 'center', alignItems: 'center', marginBottom: 24
  },
  emptyTitle: { fontSize: 20, fontWeight: '700', color: '#0F172A', marginBottom: 12 },
  emptyText: { fontSize: 14, color: '#64748B', textAlign: 'center', lineHeight: 22, marginBottom: 32 },
  refreshBtnEmpty: {
    flexDirection: 'row', alignItems: 'center', backgroundColor: '#EEF2FF',
    paddingHorizontal: 20, paddingVertical: 12, borderRadius: 100
  },
  refreshBtnTextEmpty: { color: '#4F46E5', fontSize: 15, fontWeight: '700' }
});
