import React, { useState, useEffect } from 'react';
import { 
  View, Text, StyleSheet, FlatList, TouchableOpacity, 
  SafeAreaView, TextInput, ActivityIndicator, Image, StatusBar, Platform, Linking 
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import axios from 'axios';

const ResidentsScreen = ({ navigation }) => {
  const [residents, setResidents] = useState([]);
  const [filteredResidents, setFilteredResidents] = useState([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    fetchResidents();
  }, []);

  const fetchResidents = async () => {
    try {
      const response = await axios.get('http://192.168.1.19/renter-system/api/admin/get_residents_directory.php');
      if (response.data.status === 'success') {
        setResidents(response.data.data);
        setFilteredResidents(response.data.data);
      }
    } catch (error) {
      console.log("Error fetching residents", error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleSearch = (text) => {
    setSearchQuery(text);
    if (text) {
      const lowercasedText = text.toLowerCase();
      const filtered = residents.filter(res => 
        res.name.toLowerCase().includes(lowercasedText) ||
        (res.room_no && res.room_no.toLowerCase().includes(lowercasedText)) ||
        (res.phone && res.phone.includes(text))
      );
      setFilteredResidents(filtered);
    } else {
      setFilteredResidents(residents);
    }
  };

  const openDialer = (phone) => {
    if(phone) Linking.openURL(`tel:${phone}`);
  };

  const openSMS = (phone) => {
    if(phone) Linking.openURL(`sms:${phone}`);
  };

  const getStatusBadge = (status) => {
    switch(status) {
      case 'Active':
        return { bg: '#ECFDF5', text: '#10B981', label: 'Active' };
      case 'Expiring Soon':
        return { bg: '#FEF3C7', text: '#F59E0B', label: 'Expiring Soon' };
      case 'Expired':
        return { bg: '#FEF2F2', text: '#EF4444', label: 'Expired' };
      default:
        return { bg: '#F1F5F9', text: '#64748B', label: 'Unknown' };
    }
  };

  const renderResidentCard = ({ item }) => {
    const statusObj = getStatusBadge(item.status);
    const hasDues = item.fixed_rent > 0 || item.fixed_maintenance > 0;
    const avatarUrl = item.profile_pic ? `http://192.168.1.19/renter-system/${item.profile_pic}` : null;

    return (
      <View style={styles.card}>
        <View style={styles.cardHeader}>
          <View style={styles.avatarContainer}>
            {avatarUrl ? (
              <Image source={{ uri: avatarUrl }} style={styles.avatarImage} />
            ) : (
              <Text style={styles.avatarInitial}>{item.name.charAt(0)}</Text>
            )}
          </View>
          <View style={styles.residentInfo}>
            <Text style={styles.residentName}>{item.name}</Text>
            <View style={styles.roomBadge}>
              <Text style={styles.roomBadgeText}>Room {item.room_no}</Text>
            </View>
          </View>
          <View style={[styles.statusBadge, { backgroundColor: statusObj.bg }]}>
            <Text style={[styles.statusBadgeText, { color: statusObj.text }]}>{statusObj.label}</Text>
          </View>
        </View>

        <View style={styles.divider} />

        <View style={styles.cardFooter}>
          <View style={styles.financials}>
            {hasDues ? (
              <Text style={styles.financialText}>
                <Text style={{ color: '#64748B' }}>Rent: </Text>₹{item.fixed_rent.toLocaleString('en-IN')}
                <Text style={{ color: '#E2E8F0' }}>  |  </Text>
                <Text style={{ color: '#64748B' }}>Maint: </Text>₹{item.fixed_maintenance.toLocaleString('en-IN')}
              </Text>
            ) : (
              <Text style={styles.financialTextDisabled}>No fixed dues set</Text>
            )}
          </View>
          <View style={styles.actions}>
            <TouchableOpacity style={styles.actionBtn} onPress={() => openDialer(item.phone)}>
              <Ionicons name="call" size={18} color="#4F46E5" />
            </TouchableOpacity>
            <TouchableOpacity style={styles.actionBtn} onPress={() => openSMS(item.phone)}>
              <Ionicons name="chatbubble" size={18} color="#4F46E5" />
            </TouchableOpacity>
          </View>
        </View>
      </View>
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>Directory</Text>
          <Text style={styles.headerSubtitle}>{residents.length} Residents</Text>
        </View>
        <TouchableOpacity style={styles.addBtn}>
          <Ionicons name="add" size={24} color="#fff" />
        </TouchableOpacity>
      </View>

      {/* Search Bar */}
      <View style={styles.searchContainer}>
        <View style={styles.searchBox}>
          <Ionicons name="search" size={20} color="#94A3B8" style={styles.searchIcon} />
          <TextInput 
            style={styles.searchInput}
            placeholder="Search by name, room, or phone..."
            placeholderTextColor="#94A3B8"
            value={searchQuery}
            onChangeText={handleSearch}
          />
          {searchQuery.length > 0 && (
            <TouchableOpacity onPress={() => handleSearch('')}>
              <Ionicons name="close-circle" size={20} color="#94A3B8" />
            </TouchableOpacity>
          )}
        </View>
      </View>

      {/* List */}
      {isLoading ? (
        <View style={styles.loaderCenter}>
          <ActivityIndicator size="large" color="#4F46E5" />
        </View>
      ) : (
        <FlatList 
          data={filteredResidents}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderResidentCard}
          contentContainerStyle={styles.listContent}
          showsVerticalScrollIndicator={false}
          ListEmptyComponent={
            <View style={styles.emptyState}>
              <Ionicons name="people-outline" size={64} color="#CBD5E1" />
              <Text style={styles.emptyTitle}>No residents found</Text>
              <Text style={styles.emptySubtitle}>Try adjusting your search query.</Text>
            </View>
          }
        />
      )}
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
    paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight + 10 : 0
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 24,
    paddingTop: 10,
    paddingBottom: 20,
  },
  headerTitle: { fontSize: 28, fontWeight: '800', color: '#0F172A', letterSpacing: -0.5 },
  headerSubtitle: { fontSize: 14, color: '#64748B', fontWeight: '500', marginTop: 2 },
  addBtn: {
    width: 44, height: 44, borderRadius: 22, backgroundColor: '#4F46E5',
    justifyContent: 'center', alignItems: 'center',
    shadowColor: '#4F46E5', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.3, shadowRadius: 8, elevation: 5
  },

  searchContainer: {
    paddingHorizontal: 24,
    marginBottom: 20,
  },
  searchBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 16,
    paddingHorizontal: 16,
    height: 54,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.02,
    shadowRadius: 4,
    elevation: 1
  },
  searchIcon: { marginRight: 10 },
  searchInput: { flex: 1, fontSize: 16, color: '#0F172A', fontWeight: '500' },

  listContent: { paddingHorizontal: 24, paddingBottom: 100 },
  
  card: {
    backgroundColor: '#fff',
    borderRadius: 24,
    padding: 20,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#4F46E5',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.04,
    shadowRadius: 12,
    elevation: 3
  },
  cardHeader: { flexDirection: 'row', alignItems: 'center' },
  avatarContainer: {
    width: 52, height: 52, borderRadius: 26, backgroundColor: '#EEF2FF',
    justifyContent: 'center', alignItems: 'center', marginRight: 16, overflow: 'hidden'
  },
  avatarImage: { width: '100%', height: '100%' },
  avatarInitial: { color: '#4F46E5', fontSize: 20, fontWeight: '700' },
  
  residentInfo: { flex: 1 },
  residentName: { fontSize: 17, fontWeight: '700', color: '#0F172A', marginBottom: 4 },
  roomBadge: { alignSelf: 'flex-start', backgroundColor: '#F8FAFC', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 8, borderWidth: 1, borderColor: '#E2E8F0' },
  roomBadgeText: { fontSize: 12, color: '#475569', fontWeight: '600' },
  
  statusBadge: { paddingHorizontal: 10, paddingVertical: 6, borderRadius: 12 },
  statusBadgeText: { fontSize: 11, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5 },

  divider: { height: 1, backgroundColor: '#F1F5F9', marginVertical: 16 },

  cardFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  financials: { flex: 1 },
  financialText: { fontSize: 13, color: '#0F172A', fontWeight: '700' },
  financialTextDisabled: { fontSize: 13, color: '#94A3B8', fontStyle: 'italic' },
  
  actions: { flexDirection: 'row', gap: 12 },
  actionBtn: { width: 36, height: 36, borderRadius: 18, backgroundColor: '#EEF2FF', justifyContent: 'center', alignItems: 'center' },

  loaderCenter: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  emptyState: { alignItems: 'center', marginTop: 60 },
  emptyTitle: { fontSize: 18, fontWeight: '700', color: '#0F172A', marginTop: 16, marginBottom: 8 },
  emptySubtitle: { fontSize: 15, color: '#64748B' }
});

export default ResidentsScreen;
