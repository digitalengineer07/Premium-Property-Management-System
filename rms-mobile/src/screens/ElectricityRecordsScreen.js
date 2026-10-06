import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, FlatList, ActivityIndicator, TouchableOpacity, SafeAreaView, Platform, StatusBar, Image } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import axios from 'axios';

export default function ElectricityRecordsScreen({ navigation }) {
  const [records, setRecords] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchRecords();
  }, []);

  const fetchRecords = async () => {
    try {
      const response = await axios.get('http://192.168.1.19/renter-system/api/admin/get_all_electricity.php');
      if (response.data.status === 'success') {
        setRecords(response.data.data);
      }
    } catch (error) {
      console.error("Error fetching electricity records:", error);
    } finally {
      setLoading(false);
    }
  };

  const renderItem = ({ item, index }) => (
    <View style={styles.recordItem}>
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
  );

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Ionicons name="arrow-back" size={24} color="#0F172A" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>All Electricity Records</Text>
        <View style={{ width: 40 }} />
      </View>

      {loading ? (
        <View style={styles.loaderCenter}>
          <ActivityIndicator size="large" color="#4F46E5" />
        </View>
      ) : (
        <FlatList
          data={records}
          keyExtractor={(item) => item.id.toString()}
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
  recordItem: {
    flexDirection: 'row', alignItems: 'center', marginBottom: 20,
    backgroundColor: '#fff', padding: 16, borderRadius: 20,
    shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.05, shadowRadius: 8, elevation: 2
  },
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
});
