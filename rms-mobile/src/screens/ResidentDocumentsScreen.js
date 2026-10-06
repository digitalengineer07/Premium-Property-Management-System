import React from 'react';
import { View, Text, StyleSheet, SafeAreaView, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

export default function ResidentDocumentsScreen({ navigation }) {
  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color="#0F172A" />
        </TouchableOpacity>
        <Text style={styles.title}>My Documents</Text>
      </View>
      <View style={styles.content}>
        <Ionicons name="folder-open-outline" size={80} color="#CBD5E1" />
        <Text style={styles.message}>Rent Agreements & IDs</Text>
        <Text style={styles.subMessage}>You will be able to access your securely stored documents here soon.</Text>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  header: { flexDirection: 'row', alignItems: 'center', padding: 20, backgroundColor: '#FFFFFF', borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  backBtn: { marginRight: 16 },
  title: { fontSize: 18, fontWeight: '700', color: '#0F172A' },
  content: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },
  message: { fontSize: 18, fontWeight: '600', color: '#0F172A', marginTop: 16, textAlign: 'center' },
  subMessage: { fontSize: 14, color: '#64748B', textAlign: 'center', marginTop: 8 }
});
