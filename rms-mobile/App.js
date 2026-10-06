import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { StatusBar } from 'expo-status-bar';

import LoginScreen from './src/screens/LoginScreen';
import LoginFormScreen from './src/screens/LoginFormScreen';
import AdminDashboardScreen from './src/screens/AdminDashboardScreen';
import BillingScreen from './src/screens/BillingScreen';
import ResidentsScreen from './src/screens/ResidentsScreen';
import ProfileScreen from './src/screens/ProfileScreen';
import ElectricityRecordsScreen from './src/screens/ElectricityRecordsScreen';
import PendingPaymentsScreen from './src/screens/PendingPaymentsScreen';
import NotificationsScreen from './src/screens/NotificationsScreen';
import ResidentDashboardScreen from './src/screens/ResidentDashboardScreen';
import ResidentPayScreen from './src/screens/ResidentPayScreen';
import ResidentQueriesScreen from './src/screens/ResidentQueriesScreen';
import ResidentBillsScreen from './src/screens/ResidentBillsScreen';
import ResidentDocumentsScreen from './src/screens/ResidentDocumentsScreen';
import ResidentProfileScreen from './src/screens/ResidentProfileScreen';

const Stack = createNativeStackNavigator();

export default function App() {
  return (
    <NavigationContainer>
      <StatusBar style="dark" />
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="Login" component={LoginScreen} />
        <Stack.Screen name="LoginForm" component={LoginFormScreen} />
        <Stack.Screen name="AdminDashboard" component={AdminDashboardScreen} />
        <Stack.Screen name="Billing" component={BillingScreen} />
        <Stack.Screen name="Residents" component={ResidentsScreen} />
        <Stack.Screen name="Profile" component={ProfileScreen} />
        <Stack.Screen name="ElectricityRecords" component={ElectricityRecordsScreen} />
        <Stack.Screen name="PendingPayments" component={PendingPaymentsScreen} />
        <Stack.Screen name="Notifications" component={NotificationsScreen} />
        <Stack.Screen name="ResidentDashboard" component={ResidentDashboardScreen} />
        <Stack.Screen name="ResidentPay" component={ResidentPayScreen} />
        <Stack.Screen name="ResidentQueries" component={ResidentQueriesScreen} />
        <Stack.Screen name="ResidentBills" component={ResidentBillsScreen} />
        <Stack.Screen name="ResidentDocuments" component={ResidentDocumentsScreen} />
        <Stack.Screen name="ResidentProfile" component={ResidentProfileScreen} />
      </Stack.Navigator>
    </NavigationContainer>
  );
}
