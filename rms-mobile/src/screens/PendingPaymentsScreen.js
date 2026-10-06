import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, FlatList, ActivityIndicator, TouchableOpacity, SafeAreaView, Platform, StatusBar } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import axios from 'axios';

export default function PendingPaymentsScreen({ navigation }) {
  const [records, setRecords] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchRecords();
  }, []);

  const fetchRecords = async () => {
    try {
      const response = await axios.get('http://192.168.1.19/renter-system/api/admin/get_all_pending.php');
      if (response.data.status === 'success') {
        setRecords(response.data.data);
      }
    } catch (error) {
      console.error("Error fetching pending records:", error);
    } finally {
      setLoading(false);
    }
  };

  const renderItem = ({ item }) => (
    <View style={styles.pendingItem}>
      <View style={styles.pendingInfo}>
        <Text style={styles.pendingName}>{item.name}</Text>
        <Text style={styles.pendingRoom}>Room {item.room_no}  •  {item.type}</Text>
      </View>
      <Text style={styles.pendingAmount}>₹{item.amount.toLocaleString('en-IN')}</Text>
      <View style={styles.pendingStatusBadge}>
        <Ionicons name="pin" size={10} color="#EF4444" style={{marginRight: 4}} />
        <Text style={styles.pendingStatusText}>Due</Text>
      </View>
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Ionicons name="arrow-back" size={24} color="#0F172A" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Pending Payments</Text>
        <View style={{ width: 40 }} />
      </View>

      {loading ? (
        <View style={styles.loaderCenter}>
          <ActivityIndicator size="large" color="#4F46E5" />
        </View>
      ) : (
        <FlatList
          data={records}
          keyExtractor={(item) => item.id.toString() + item.type}
          renderItem={renderItem}
          contentContainerStyle={styles.listContainer}
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
    justifyContent: 'center', alignItems: 'flex-start'
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
  pendingItem: {
    flexDirection: 'row', alignItems: 'center', marginBottom: 16,
    backgroundColor: '#fff', padding: 20, borderRadius: 20,
    shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.05, shadowRadius: 8, elevation: 2
  },
  pendingInfo: { flex: 1 },
  pendingName: { fontSize: 16, fontWeight: '700', color: '#4F46E5', marginBottom: 4 },
  pendingRoom: { fontSize: 13, color: '#64748B', fontWeight: '500' },
  pendingAmount: { fontSize: 16, fontWeight: '800', color: '#0F172A', marginRight: 16 },
  pendingStatusBadge: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FEE2E2', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 12 },
  pendingStatusText: { color: '#DC2626', fontSize: 12, fontWeight: '700' },
});
