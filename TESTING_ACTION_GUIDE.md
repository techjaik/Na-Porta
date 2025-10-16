# 🎯 NA PORTA - TESTING ACTION GUIDE

**Complete guide to test both sites and verify all fixes**

---

## 🚀 QUICK START (5 MINUTES)

### Step 1: Run Master Test Suite (2 min)
```
https://naporta.free.nf/run_all_tests.php
```
**What it does**: Runs all tests and shows overall score
**Expected Result**: Score should be 90%+

### Step 2: Run Security Audit (1 min)
```
https://naporta.free.nf/security_audit.php
```
**What it does**: Checks for security vulnerabilities
**Expected Result**: No critical issues, all security measures in place

### Step 3: Run Functionality Tests (1 min)
```
https://naporta.free.nf/functionality_test.php
```
**What it does**: Tests all database tables and data
**Expected Result**: All tables exist, data is intact

### Step 4: Test Checkout Button (1 min)
```
https://naporta.free.nf/test_checkout_direct.php
```
**What it does**: Creates a test order
**Expected Result**: Order created successfully with ID

---

## 📋 DETAILED TESTING CHECKLIST

### MAIN SITE TESTS

#### 1. Homepage (https://naporta.free.nf/)
- [ ] Page loads in < 2.5 seconds
- [ ] Featured products display
- [ ] Categories show
- [ ] Navigation menu works
- [ ] Mobile responsive
- [ ] No console errors

#### 2. Products Page (https://naporta.free.nf/products.php)
- [ ] Products load with pagination
- [ ] 12 products per page
- [ ] Category filter works
- [ ] Search works (if implemented)
- [ ] Add to cart button works
- [ ] Page loads in < 2.5 seconds

#### 3. Cart Page (https://naporta.free.nf/cart.php)
- [ ] Cart items display
- [ ] Update quantity works (AJAX, no reload)
- [ ] Remove item works (AJAX, no reload)
- [ ] Totals calculate correctly
- [ ] Checkout button visible
- [ ] Continue shopping button works

#### 4. Checkout Page (https://naporta.free.nf/checkout.php)
- [ ] **Requires login** - redirects if not logged in
- [ ] Address form displays
- [ ] Address fields validate
- [ ] Payment method options show
- [ ] Order summary displays
- [ ] **"Finalizar Pedido" button works** ✅
- [ ] Order created successfully
- [ ] Success message shows with order ID
- [ ] Cart cleared after order

#### 5. Account Page (https://naporta.free.nf/account.php)
- [ ] Profile information displays
- [ ] Edit profile works
- [ ] Address management works
- [ ] Add address works
- [ ] Edit address works
- [ ] Delete address works
- [ ] Set default address works

#### 6. Authentication
- [ ] Registration works (https://naporta.free.nf/auth/register.php)
- [ ] Login works (https://naporta.free.nf/auth/login.php)
- [ ] Logout works
- [ ] Session persists
- [ ] Remember me works (if implemented)

### ADMIN SITE TESTS

#### 1. Admin Login (https://naporta.free.nf/admin/login.php)
- [ ] Login form displays
- [ ] Login works with correct credentials
- [ ] Error shows with wrong credentials
- [ ] Redirects to dashboard on success

#### 2. Admin Dashboard (https://naporta.free.nf/admin/)
- [ ] Dashboard loads
- [ ] Statistics display (users, products, orders)
- [ ] Recent orders show
- [ ] Navigation menu works
- [ ] All links functional

#### 3. Product Management (https://naporta.free.nf/admin/products.php)
- [ ] Products list displays
- [ ] Pagination works
- [ ] Create product works
- [ ] Edit product works
- [ ] Delete product works
- [ ] Upload image (file) works
- [ ] Upload image (URL) works
- [ ] Activate/deactivate works
- [ ] Search/filter works

#### 4. Category Management (https://naporta.free.nf/admin/categories.php)
- [ ] Categories list displays
- [ ] Create category works
- [ ] Edit category works
- [ ] Delete category works
- [ ] Upload image works
- [ ] Sort order works
- [ ] Activate/deactivate works

#### 5. Banner Management (https://naporta.free.nf/admin/banners.php)
- [ ] Banners list displays
- [ ] Create banner works
- [ ] Edit banner works
- [ ] Delete banner works
- [ ] Upload image works
- [ ] Activate/deactivate works

#### 6. Order Management (https://naporta.free.nf/admin/orders.php)
- [ ] Orders list displays
- [ ] Pagination works
- [ ] View order details works
- [ ] Update order status works
- [ ] Print order works
- [ ] Search/filter works

#### 7. User Management (https://naporta.free.nf/admin/users.php)
- [ ] Users list displays
- [ ] View user details works
- [ ] Edit user works
- [ ] Deactivate user works
- [ ] View user addresses works

---

## 🔒 SECURITY VERIFICATION

### Test SQL Injection Prevention
1. Go to checkout page
2. Try entering: `'; DROP TABLE orders; --` in address field
3. **Expected**: Error message, no data loss

### Test XSS Prevention
1. Go to checkout page
2. Try entering: `<script>alert('XSS')</script>` in address field
3. **Expected**: Text displayed as-is, no script execution

### Test CSRF Protection
1. Open browser console
2. Check for CSRF token in forms
3. **Expected**: Token present in all POST forms

### Test Authentication
1. Try accessing admin page without login
2. **Expected**: Redirected to login page
3. Try accessing checkout without login
4. **Expected**: Redirected to login page

---

## ⚡ PERFORMANCE VERIFICATION

### Measure Page Load Times
1. Open DevTools (F12)
2. Go to Network tab
3. Visit each page
4. Check load time

**Expected Times**:
- Homepage: < 2.5s
- Products: < 2.5s
- Cart: < 2s
- Checkout: < 2.5s
- Admin Dashboard: < 3s

### Check for Console Errors
1. Open DevTools (F12)
2. Go to Console tab
3. Visit each page
4. **Expected**: No errors (warnings OK)

### Check for Network Errors
1. Open DevTools (F12)
2. Go to Network tab
3. Visit each page
4. **Expected**: No 404 or 500 errors

---

## 📱 MOBILE TESTING

### Test on Mobile Device
1. Visit: https://naporta.free.nf on mobile
2. Check responsiveness
3. Test all buttons
4. Test forms
5. Test navigation

**Expected**: Everything works perfectly on mobile

### Test on Tablet
1. Visit: https://naporta.free.nf on tablet
2. Check layout
3. Test all features

**Expected**: Everything works perfectly on tablet

---

## 🐛 ISSUE REPORTING

If you find any issues:

1. **Document the issue**:
   - What page/feature?
   - What did you do?
   - What happened?
   - What should happen?

2. **Take a screenshot**

3. **Check console for errors** (F12)

4. **Report to developer**

---

## ✅ FINAL VERIFICATION CHECKLIST

- [ ] All test scripts run successfully
- [ ] Security audit passes
- [ ] Functionality tests pass
- [ ] Checkout button works
- [ ] All pages load fast
- [ ] No console errors
- [ ] Mobile responsive
- [ ] Admin site works
- [ ] All buttons work
- [ ] All forms validate
- [ ] Orders create successfully
- [ ] Cart updates without reload
- [ ] Addresses save correctly
- [ ] User profiles work
- [ ] No security vulnerabilities

---

## 🎉 SUCCESS CRITERIA

✅ **Application is PRODUCTION READY when**:
- All test scripts pass
- No critical security issues
- All features work
- Performance is good
- Mobile responsive
- No console errors
- Admin site functional

---

## 📞 SUPPORT

If you need help:
1. Check the test reports
2. Review the error messages
3. Check browser console (F12)
4. Contact developer

---

**Happy Testing!** 🚀


